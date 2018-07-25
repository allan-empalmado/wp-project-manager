/**
 * Required jQuery methods 
 * 
 * @type Object
 */
var PM_Task = {
    init: function() {
        this.datepicker();
        this.sortable();
    },

    sortable: function (el, binding, vnode) {
        var $ = jQuery;
        var component = vnode.context;
        

        $(el).sortable({
            cancel: '.nonsortable,form',
            connectWith: '.pm-connected-sortable',
            placeholder: "ui-state-highlight",
            
            update: function(event, ui) {
                if(ui.sender) {
                    PM_Task.receive(this, vnode, ui, event);
                } else {
                    let listId = $(ui.item).closest('ul.pm-todolist-content').data('list_id');
                    let todos  = $(ui.item).closest('ul.pm-todolist-content').find('li.pm-todo');
                    let orders = PM_Task.sorting(todos);
                    
                    component.taskOrder({
                        list_id: listId,
                        orders: orders
                    });
                };
            }
        });
    },

    receive: function(self, vnode, ui) {
        
        var $ = jQuery,
            listId = $(ui.item).closest('ul.pm-todolist-content').data('list_id'),
            taskId = $(ui.item).data('id'),
            todos  = $(ui.item).closest('ul.pm-todolist-content').find('li.pm-todo'),
            orders = PM_Task.sorting(todos);

        vnode.context.taskReceive({
            list_id: listId,
            task_id: taskId,
            orders: orders,
            receive: 1
        });
    },

    sorting: function(todos) {
        todos = todos || [];
        var $ = jQuery,
            orders = [];

        // var newOrder = {},
        //     orders = [],
        //     ids = [],
        //     send_data = [];
            
        // finding new order sequence and old orders
        todos.each( function(index, e) {
            let task_id = $(e).data('id');
            
            orders.push({
                index: index,
                id: task_id
            });
            

            //ids.push(task_id);
           

            // var task_index = component.getIndex(component.list.incomplete_tasks.data, task_id,'id');
            
            // if (task_index !== false && typeof component.list.incomplete_tasks !== 'undefined') {
            //     component.list.incomplete_tasks.data[task_index].order = order;
            // }

            // if (task_index === false) {
            //     var task_index = component.getIndex(component.list.complete_tasks.data, task_id,'id');

            //     if ( task_index !== false && typeof component.list.complete_tasks !== 'undefined') {
            //         component.list.complete_tasks.data[task_index].order = order;
            //     }
            // }

        }); 

        // var after_revers_order = orders.sort(),
        //     after_revers_order = after_revers_order.reverse();

        // after_revers_order.forEach(function(order, key) {
        //     send_data.push({
        //         id: ids[key],
        //         order: order
        //     });
        // });

        // var data = {
        //     task_orders: send_data,
        //     board_id: component.list.id,
        //     board_type: 'task_list'
        // }
        //component.taskOrder(data);

        return orders;
    },

    datepicker: function(el, binding, vnode) {
        var $ = jQuery;
        $( '.pm-date-field').datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true,
            yearRange: '-50:+5',
            onSelect: function(dateText) {
                vnode.context.$root.$emit( 'pm_date_picker', { field: 'datepicker', date: dateText } );
            }
        });

        $( ".pm-date-picker-from" ).datepicker({
            dateFormat: 'yy-mm-dd',
            changeYear: true,
            changeMonth: true,
            numberOfMonths: 1,
            onClose: function( selectedDate ) {
                $( ".pm-date-picker-to" ).datepicker( "option", "minDate", selectedDate );
            },
            onSelect: function(dateText) {
                vnode.context.$root.$emit( 'pm_date_picker', { field: 'datepicker_from', date: dateText, self: this } );
            }
        });

        $( ".pm-date-picker-to" ).datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true,
            numberOfMonths: 1,
            onClose: function( selectedDate ) {
                $( ".pm-date-picker-from" ).datepicker( "option", "maxDate", selectedDate );
            },
            onSelect: function(dateText) {
                vnode.context.$root.$emit( 'pm_date_picker', { field: 'datepicker_to', date: dateText } );
            }
        });

        $( ".pm-date-time-picker-from" ).datepicker({
            dateFormat: 'yy-mm-dd',
            changeYear: true,
            changeMonth: true,
            numberOfMonths: 1,
            onClose: function( selectedDate ) {
                $( ".pm-date-time-picker-to" ).datepicker( "option", "minDate", selectedDate );
            },
            onSelect: function(dateText) {
                
            }
        });

        $( ".pm-date-time-picker-to" ).datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true,
            numberOfMonths: 1,
            onClose: function( selectedDate ) {
                $( ".pm-date-time-picker-from" ).datepicker( "option", "maxDate", selectedDate );
            },
            onSelect: function(dateText) {
               
            }
        });
    },

    disableLineBreak: function(element) {
        jQuery(element).on( 'keypress', function(e) {
            if ( e.keyCode == 13 && !e.shiftKey ) {
                e.preventDefault();
            }
        });
    }
}

//Register a global custom directive called v-pm-datepicker
pm.Vue.directive('pm-datepicker', {
    inserted: function (el, binding, vnode) {
        PM_Task.datepicker( el, binding, vnode );
    },
});

// Register a global custom directive called v-pm-sortable
pm.Vue.directive('pm-sortable', {
    inserted: function (el, binding, vnode) {
        PM_Task.sortable(el, binding, vnode);
    }
});

// Register a global custom directive called v-pm-sortable
pm.Vue.directive('pm-tiptip', {

    update: function () {
        jQuery('.pm-tiptip').tipTip();
    }
});

// Register a global custom directive called v-pm-sortable
pm.Vue.directive('prevent-line-break', {

    inserted: function (element) {
        PM_Task.disableLineBreak(element);
    }
});
