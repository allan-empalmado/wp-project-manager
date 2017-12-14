<?php

namespace WeDevs\PM\Task_List\Controllers;

use WP_REST_Request;
use WeDevs\PM\Task_List\Models\Task_List;
use League\Fractal;
use League\Fractal\Resource\Item as Item;
use League\Fractal\Resource\Collection as Collection;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use WeDevs\PM\Common\Traits\Transformer_Manager;
use WeDevs\PM\Task_List\Transformers\Task_List_Transformer;
use WeDevs\PM\Common\Models\Boardable;
use WeDevs\PM\Common\Traits\Request_Filter;
use WeDevs\PM\Milestone\Models\Milestone;

class Task_List_Controller {

    use Transformer_Manager, Request_Filter;

    public function index( WP_REST_Request $request ) {
        $project_id = $request->get_param( 'project_id' );
        $per_page = $request->get_param( 'per_page' );
        $per_page_from_settings = pm_get_settings( 'list_per_page' );
        $per_page_from_settings = $per_page_from_settings ? $per_page_from_settings : 15;
        $per_page = $per_page ? $per_page : $per_page_from_settings;

        $page = $request->get_param( 'page' );
        $page = $page ? $page : 1;

        $task_lists = Task_List::where( 'project_id', $project_id)
            ->orderBy( 'created_at', 'DESC' )
            ->paginate( $per_page, ['*'], 'page', $page );

        $task_list_collection = $task_lists->getCollection();

        $resource = new Collection( $task_list_collection, new Task_List_Transformer );
        $resource->setPaginator( new IlluminatePaginatorAdapter( $task_lists ) );

        return $this->get_response( $resource );
    }

    public function show( WP_REST_Request $request ) {
        $project_id   = $request->get_param( 'project_id' );
        $task_list_id = $request->get_param( 'task_list_id' );

        $task_list = Task_List::with( 'tasks' )
            ->where( 'id', $task_list_id )
            ->where( 'project_id', $project_id )
            ->first();

        $resource = new Item( $task_list, new Task_List_Transformer );

        return $this->get_response( $resource );
    }

    public function store( WP_REST_Request $request ) {
        $data = $this->extract_non_empty_values( $request );
        $milestone_id = $request->get_param( 'milestone' );

        $milestone = Milestone::find( $milestone_id );
        $task_list = Task_List::create( $data );

        if ( $milestone ) {
            $this->attach_milestone( $task_list, $milestone );
        }

        $resource = new Item( $task_list, new Task_List_Transformer );

        $message = [
            'message' => pm_get_text('success_messages.task_list_created')
        ];

        return $this->get_response( $resource, $message );
    }

    public function update( WP_REST_Request $request ) {
        $data = $this->extract_non_empty_values( $request );
        $project_id   = $request->get_param( 'project_id' );
        $task_list_id = $request->get_param( 'task_list_id' );
        $milestone_id = $request->get_param( 'milestone' );

        $milestone = Milestone::find( $milestone_id );
        $task_list = Task_List::where( 'id', $task_list_id )
            ->where( 'project_id', $project_id )
            ->first();

        $task_list->update_model( $data );

        if ( $milestone ) {
            $this->attach_milestone( $task_list, $milestone );
        }

        $resource = new Item( $task_list, new Task_List_Transformer );

        $message = [
            'message' => pm_get_text('success_messages.task_list_updated')
        ];

        return $this->get_response( $resource, $message );
    }

    public function destroy( WP_REST_Request $request ) {
        // Grab user inputs
        $project_id   = $request->get_param( 'project_id' );
        $task_list_id = $request->get_param( 'task_list_id' );

        // Select the task list to be deleted
        $task_list = Task_List::where( 'id', $task_list_id )
            ->where( 'project_id', $project_id )
            ->first();

        // Delete relations
        $this->detach_all_relations( $task_list );

        // Delete the task list
        $task_list->delete();
        $message = [
            'message' => pm_get_text('success_messages.task_list_deleted')
        ];

        return $this->get_response(false, $message);
    }

    private function attach_milestone( Task_List $task_list, Milestone $milestone ) {
        $boardable = Boardable::where( 'boardable_id', $task_list->id )
            ->where( 'boardable_type', 'task_list' )
            ->where( 'board_type', 'milestone' )
            ->first();

        if ( !$boardable ) {
            $boardable = Boardable::firstOrCreate([
                'boardable_id'   => $task_list->id,
                'boardable_type' => 'task_list',
                'board_id'       => $milestone->id,
                'board_type'     => 'milestone'
            ]);
        } else {
            $boardable->update([
                'board_id' => $milestone->id
            ]);
        }
    }

    private function detach_all_relations( Task_List $task_list ) {
        $task_list->boardables()->delete();

        $comments = $task_list->comments;
        foreach ( $comments as $comment ) {
            $comment->replies()->delete();
            $comment->files()->delete();
        }

        $task_list->comments()->delete();
        $task_list->files()->delete();
        $task_list->milestones()->detach();
    }

    public function attach_users( WP_REST_Request $request ) {
        $project_id = $request->get_param( 'project_id' );
        $task_list_id = $request->get_param( 'task_list_id' );

        $task_list = Task_List::where( 'id', $task_list_id )
            ->where( 'project_id', $project_id )
            ->first();

        $user_ids = explode( ',', $request->get_param( 'users' ) );

        if ( !empty( $user_ids ) ) {
            foreach ( $user_ids as $user_id ) {
                $data = [
                    'board_id' => $task_list->id,
                    'board_type' => 'task_list',
                    'boardable_id' => $user_id,
                    'boardable_type' => 'user'
                ];
                Boardable::firstOrCreate( $data );
            }
        }

        $resource = new Item( $task_list, new Task_List_Transformer );

        return $this->get_response( $resource );
    }

    public function detach_users( WP_REST_Request $request ) {
        $project_id = $request->get_param( 'project_id' );
        $task_list_id = $request->get_param( 'task_list_id' );

        $task_list = Task_List::where( 'id', $task_list_id )
            ->where( 'project_id', $project_id )
            ->first();

        $user_ids = explode( ',', $request->get_param( 'users' ) );

        $task_list->users()->whereIn( 'boardable_id', $user_ids )->delete();

        $resource = new Item( $task_list, new Task_List_Transformer );

        return $this->get_response( $resource );
    }
}