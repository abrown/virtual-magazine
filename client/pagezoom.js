/**
 * Page Zoom jQuery Plugin, version 0.1
 * http://www.github.com/andrewsbrown/pagezoom
 * 
 * This plugin zooms into an element using the HTML5 canvas. The resulting image
 * is draggable within that element
 * 
 * Copyright 2012 Andrew Brown
 * Released under the ... license
 * http://jquery.org/license
 *
 * Date: 29 September 2012
 */
(function( $ ) {
    $.widget( "andrewsbrown.pagezoom", {
        
        /**
         * Default options
         */
        options: { 
            DRAGGABLE: true,
            ZOOM_MIN: 1,
            ZOOM_DEFAULT: 2,
            ZOOM_MAX: 5,
            FRAMES_PER_SECOND: 60 // number of times per second the page flip is rendered
        },
        
        /**
         * Zoom level; 1 is normal, 2 doubles the size, etc.
         */
        zoom: 1,
        
        /**
         * Determine whether the content is being dragged or not
         */
        dragging: false,
        
        /**
         * DOM element wrapping the viewable content; will have a "zoom-wrapper"
         * as a CSS class
         */
        wrapper: null,
                  
        /**
         * Constructor
         */
        _create: function() {
            // create wrapper
            this.element.wrap('<div class="zoom-wrapper" />');
            this.wrapper = this.element.parent();
            this.wrapper.css({
                position: 'relative', // must be relative for dragging to work correctly
                height: this.element.height(),
                width: this.element.width(),
                'background-color': '#B5B0B0'
            });
            // make draggable
            if(this.options.DRAGGABLE){
                this.element.draggable({
                    disabled: true
                });
            }
            // set CSS easing
            this.element.css('transition', 'transform 0.8s ease');
            this.element.css('OTransition', '-o-transform 0.8s ease');
            this.element.css('msTransition', '-ms-transform 0.8s ease');
            this.element.css('MozTransition', '-moz-transform 0.8s ease');
            this.element.css('WebkitTransition', '-webkit-transform 0.8s ease');          
            // set event handlers
            var self = this;
            // on double-click, zoom to the default level
            this.element.dblclick(function(e){
                if(self.zoom > 1){
                    self.resetZoom.call(self);
                }
                else{
                    var offset = self._getClickOffset.call(self, e);
                    self.zoomTo.call(self, self.options.ZOOM_DEFAULT, offset.x, offset.y);
                }
            });
            // on escape, zoom to normal
            $(window).keyup(function(event) {
                if( self.zoom !== 1 && event.keyCode === 27 ) {
                    self.resetZoom.call(self);
                }
            });
            // set touch event handlers
            this.element.bind('touchstart', function(e){
                e.preventDefault();
                if(self.element.data('draggable').options.disabled) return;
                self.element.data('draggable')._mouseStart(e.originalEvent.targetTouches[0]);
            });
            this.element.bind('touchmove', function(e){
                e.preventDefault();
                if(self.element.data('draggable').options.disabled) return;
                self.element.data('draggable')._mouseDrag(e.originalEvent.targetTouches[0]);
            });
            this.element.bind('touchend touchcancel', function(e){
                e.preventDefault();
                if(self.element.data('draggable').options.disabled) return;
                self.element.data('draggable')._mouseStop(e.originalEvent.targetTouches[0]);
            });
        },
        
        zoomIn: function(){
            this.zoomTo(this.options.ZOOM_DEFAULT);
        },
        
        /**
         * Zoom to the given zoom-level; 1 is the normal view, 2 doubles the size, etc.
         */
        zoomTo: function(zoom, x, y){
            if(this.zoom >= this.options.ZOOM_MIN && this.zoom <= this.options.ZOOM_MAX){
                // enable dragging
                if(this.options.DRAGGABLE){
                    this.element.draggable('enable');
                }
                // set zoomed styles
                this.wrapper.css({
                    border: '1px inset',
                    overflow: 'hidden'
                });
                // set zoom
                this.zoom = zoom;
                // calculate translations
                if( x && y ){
                    var dx = ((this.element.width() / 2) - x) * this.zoom;
                    var dy = ((this.element.height() / 2) - y) * this.zoom;
                    // scale and translate
                    this._transform('translate(' + dx + 'px, ' + dy + 'px) scale(' + this.zoom + ', ' + this.zoom + ')');
                }
                // or just scale
                else{
                    this._transform('scale(' + this.zoom + ', ' + this.zoom + ')');
                }
                // trigger event
                this._trigger('zoomed');
            }
        },
        
        /**
         * Zoom to normal view and move to the top left corner
         */
        resetZoom: function(){
            if(this.zoom >= this.options.ZOOM_MIN && this.zoom <= this.options.ZOOM_MAX){
                // disable dragging
                if(this.options.DRAGGABLE){
                    this.element.draggable('disable');
                }
                // set overflow
                this.wrapper.css({
                    border: 'none',
                    overflow: 'visible'
                });
                // set zoom
                this.zoom = 1;
                // scale and translate
                this._transform('scale(' + this.zoom + ', ' + this.zoom + ')');
                // ensure translate
                $(this.element).css({
                    left: 0, 
                    top: 0
                });
                // trigger event
                this._trigger('reset');
            }
        },
        
        /**
         * Get the mouse coordinates from the given event in relation to the
         * base element
         */
        _getClickOffset: function(e){
            var offset = this.wrapper.offset();
            return {
                x: e.pageX - offset.left, 
                y: e.pageY - offset.top
            };
        },
        
        /**
         * Perform CSS3 transforms
         */
        _transform: function(transform){
            this.element.css('transformOrigin', 'left top');
            this.element.css(transform, transform);
            this.element.css('OTransform', transform);
            this.element.css('msTransform', transform);
            this.element.css('MozTransform', transform);
            this.element.css('WebkitTransform', transform);
        },
 
        /**
         * Display error messages; TODO: popup box
         */
        err: function(message){
            alert(message);
            if(window.console) console.log(message);
        },
        
        /**
         * Use the destroy method to clean up any modifications your widget has made to the DOM
         */
        destroy: function() {
            this.element.find('.zoom-wrapper').children().unwrap()
            // In jQuery UI 1.8, you must invoke the destroy method from the base widget
            $.Widget.prototype.destroy.call( this );
        // In jQuery UI 1.9 and above, you would define _destroy instead of destroy and not call the base method
        }
    });
}( jQuery ) );

