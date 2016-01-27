(function ($) {
    $.fn.yiiGridViewRow = function (method) {
        if (typeof method === 'string' && methods[method]) {
            var row = new Row(this);

            return methods[method].apply(row, Array.prototype.slice.call(arguments, 1));
        } else if (typeof method === 'object' || !method) {
            var row = new Row(this);

            return this;
        } else {
            $.error('Method ' + method + ' does not exist on jQuery.yiiGridViewRow');

            return false;
        }
    };

    function Row($el) {
        this.$el = $el;
    }

    $.extend(Row.prototype, {
        getGridView: function() {
            return this.$el.closest('.grid-view');
        },

        getSortable: function() {
            return this.$el.closest('.ui-sortable');
        },

        getSortableCell: function() {
            return this.$el.find('.sortable-cell');
        },

        getPositionEl: function() {
            return this.getSortableCell().find('.label');
        },

        getPosition: function() {
            return this.getSortableCell().data('position');
        },

        isSortable: function() {
            return this.getPosition() != 0;
        },

        getCount: function() {
            return this.getSortable().sortable('option', 'modelsCount');
        },

        isFirst: function() {
            return this.getPosition() == 1;
        },

        isLast: function() {
            return this.getPosition() == this.getCount();
        },

        isPositionValid: function(position) {
            position = parseInt(position);

            if (isNaN(position)) {
                return false;
            }

            return (position >= 1 && position <= this.getCount());
        },

        resetPosition: function() {
            this.getPositionEl().text(this.getPosition());
        },

        getClass: function() {
            return this.getSortable().sortable('option', 'modelClass');
        },

        getPk: function() {
            return this.$el.data('key');
        },

        getBaseUrl: function() {
            return this.getSortable().sortable('option', 'baseUrl');
        },

        isMoveConfirmed: function() {
            return this.getSortable().sortable('option', 'confirmMove');
        },

        getMoveConfirmationText: function() {
            return this.getSortable().sortable('option', 'moveConfirmationText');
        },

        move: function(action, additionalParams) {
            var row = this;

            if (!this.isSortable()) {
                return;
            }

            if (this.isMoveConfirmed() && !confirm(this.getMoveConfirmationText())) {
                this.resetPosition();
                this.getSortable().sortable('cancel');

                return;
            }

            this.getPositionEl().removeClass('label-info').addClass('label-warning');

            var params = {
                modelClass: this.getClass(),
                modelPk: this.getPk()
            };
            var allParams = !additionalParams ? params : $.extend({}, params, additionalParams);

            $.post(
                this.getBaseUrl() + action,
                allParams,
                function () {
                    row.getPositionEl().removeClass('label-warning').addClass('label-success');
                    row.getGridView().yiiGridView('applyFilter');
                }
            );
        }
    });

    var methods = {
        moveToPosition: function(position) {
            if (!this.isPositionValid(position)) {
                this.resetPosition();

                return;
            }

            if (position != this.getPosition()) {
                this.move('move-to-position', { position: position });
            }
        },

        moveWithDragAndDrop: function() {
            var $prevRow = this.$el.prev();
            if ($prevRow.length) {
                var prevRow = new Row($prevRow);
                if (prevRow.isSortable()) {
                    this.move('move-after', { pk: prevRow.getPk() });

                    return;
                }
            }

            var $nextRow = this.$el.next();
            if ($nextRow.length) {
                var nextRow = new Row($nextRow);
                if (nextRow.isSortable()) {
                    this.move('move-before', { pk: nextRow.getPk() });
                    return;
                }
            }

            this.getSortable().sortable('cancel');
        },

        moveForward: function() {
            if (!this.isFirst()) {
                this.move('move-to-position', { position: this.getPosition() - 1 });
            }
        },

        moveBack: function() {
            if (!this.isLast()) {
                this.move('move-to-position', { position: this.getPosition() + 1 });
            }
        },

        moveAsFirst: function() {
            if (!this.isFirst()) {
                this.move('move-as-first');
            }
        },

        moveAsLast: function() {
            if (!this.isLast()) {
                this.move('move-as-last');
            }
        }
    };
})(window.jQuery);
