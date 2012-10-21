(function( $ ) {
    $.widget( "andrewsbrown.pageslide", {
        
        /**
         * Default options
         */
        options: { 
            PAGE_WIDTH: 400,        // dimensions of a page in the book
            PAGE_HEIGHT: 250,       // dimensions of a page in the book
            PAGE_PADDING: 3,        // space between pages
            FRAMES_PER_SECOND: 30,  // number of times per second the page flip is rendered
            BASE_URL: 'http://www.example.com/[page#].html?...', // URL to load pages from, the page number will replace "[page#]"; for this, pages are 1-indexed (i.e. start with 1, 2, 3...)
            use_ajax_loading: false,// uses the BASE_URL above to load pages into the book
            show_buttons: false     // show next/previous buttons
        },
        
        /**
         * By saving certain state variables in this object, we avoid repeating
         * calculations
         */
        state: {
            length: 0,              // number of pages loaded; this tracks how many pages have been added or are being added to the book. It is preferable to use this than this.pages.length so as not to duplicate AJAX calls to the same pages.
            ajax_load_failed: false,// set to true when no more pages can be loaded
            drag_start: 0,
            progress: 0,
            target: 0,
            dragging: false,
            moving: false,
            previous_element: undefined,
            current_element: undefined,
            next_element: undefined
        },
        
        /**
         * The DOM element containing the pages to flip
         */
        book: null,
        
        /**
         * The DOM elements contained in a book
         */
        pages: [],
               
        /**
         * The current index of the page that is being swiped/interacted with;
         * if no pages are being swiped, it defaults to the current page
         */
        page: 0,
        
        /**
         * The mouse position
         */
        mouse: {
            x: 0, 
            y: 0
        },
 
        /**
         * Constructor
         */
        _create: function() {
            var self = this;
            
            // get and style book
            this.book = $(this.element);
            if( !this.book ) this.err('Could not find book element.');
            if( !this.book.hasClass('book') ) this.book.addClass('book');
            this.book.css({
                height: this.options.PAGE_HEIGHT,
                width: this.options.PAGE_WIDTH,
                position: 'relative',
                overflow: 'hidden'
            /*
                    background-color: '#e7e9ea'
                    background-image: url('/client/images/loading.gif');
                    background-position: center;
                    background-repeat: no-repeat;
                 */
            });
            
            // get pages
            this.pages = this.book.find('.page');
            // add from URL
            if( this.options.use_ajax_loading ){
                this.addPage(0, this.options.BASE_URL.replace('[page#]', '1'));
                this.addPage(1, this.options.BASE_URL.replace('[page#]', '2'));
                this.addPage(2, this.options.BASE_URL.replace('[page#]', '3'));
            }
            // add from DOM
            else{
                this.pages.each(function(i, el){
                    self.addPage(i, el);
                });
            }
            
            // create buttons
            if(this.options.show_buttons){
                $('<button class="previous">&#9664;</button>').prependTo(this.book).css({
                    position: 'absolute',
                    top: (this.options.PAGE_HEIGHT)/2,
                    left: 30,
                    'z-index': 10
                }).click(function(e){
                    e.stopPropagation();
                    e.preventDefault();
                    self.previous();
                });
                $('<button class="next">&#9654;</button>').prependTo(this.book).css({
                    position: 'absolute',
                    top: (this.options.PAGE_HEIGHT)/2,
                    right: 30,
                    'z-index': 10
                }).click(function(e){
                    e.stopPropagation();
                    e.preventDefault();
                    self.next();
                });
            }
            
            // start rendering
            setInterval( function(){
                self.render.call(self)
            }, 1000 / this.options.FRAMES_PER_SECOND );
            
            // set event handlers
            $(document).mousemove(function(e){
                self._mouseMoveHandler.call(self, e)
            });
            $(document).mousedown(function(e){
                self._mouseDownHandler.call(self, e)
            });
            $(document).mouseup(function(e){
                self._mouseUpHandler.call(self, e)
            });
            // set touch event handlers
            this.book.bind('touchstart', function(e){
                e.preventDefault();
                self._mouseMoveHandler.call(self, e.originalEvent.targetTouches[0]);
                self._mouseDownHandler.call(self, e.originalEvent.targetTouches[0]);
            });
            this.book.bind('touchmove', function(e){
                e.preventDefault();
                self._mouseMoveHandler.call(self, e.originalEvent.targetTouches[0]);
            });
            this.book.bind('touchend touchcancel', function(e){
                e.preventDefault();
                self._mouseUpHandler.call(self, e.originalEvent.targetTouches[0]);
            });
            // trigger
            this._trigger('created');
        },
        
        /**
         * Add a page to the book
         */
        addPage: function(index, url_or_element){
            this.state.length++;
            // actual work to add a page
            var _add = function (index, element, must_add_element){
                // if necessary, add element @TODO simplify this
                if( must_add_element ){
                    var added = false;
                    this.pages.each(function(i, el){
                        var id = parseInt($(el).attr('id').replace('page#', ''));
                        // add before
                        if(index < id){
                            added = true;
                            $(el).before(element);
                            return false;
                        }
                    });
                    // add after
                    if( !added ){
                        if( this.pages.length < 1 ) this.book.prepend(element);
                        else this.book.find('.page:last').after(element);
                    }
                }
                // reset pages
                this.pages = this.book.find('.page');
                // add ID, classes
                $(element).attr('id', 'page#'+index);
                $(element).addClass('page');
                // position pages; evens on the left, odds on the right
                $(element).css({
                    display: (index) ? 'none' : 'block',
                    width: this.options.PAGE_WIDTH,
                    height: this.options.PAGE_HEIGHT,
                    position: 'absolute', 
                    top: 0, 
                    left: 0,
                    overflow: 'hidden', // pages must fit in PAGE_WIDTH x PAGE_HEIGHT area
                    'background-color': '#fff' // by default, page backgrounds will be white
                });
            }
            // determine whether to add now or after AJAX call
            var self = this;
            if(url_or_element instanceof Element){
                _add.call(self, index, url_or_element, false); // add immediately
            }
            else{
                if(self.state.ajax_load_failed) return;
                $.get(url_or_element)
                .success(function(data, status, xhr){
                    _add.call(self, index, $(data), true); // add after AJAX
                })
                .error(function(data, status, xhr){
                    self.warn('No more pages to load. No more pages will be loaded.');
                    self.state.ajax_load_failed = true;
                });
            }
        },
        
       
        /**
         * Flip to the next page
         */
        next: function(){
            // make sure next page can be moved
            if(this.options.use_ajax_loading && this.page + 2 >= this.state.length){
                this.addPage(this.state.length, this.options.BASE_URL.replace('[page#]', this.state.length + 1));
            }
            // make sure next page can be moved
            if(this.page + 1 >= this.pages.length || this.state.moving){
                return;
            }
            // set target
            this.state.target = -1;
            this.state.progress = 0;
            this.state.moving = true;
            this.state.previous_element = undefined;
            this.state.current_element = $(this.pages.get(this.page)).css('display', 'block').css('left', 0);
            this.state.next_element = (this.page + 1 >= this.pages.length) ? undefined : $(this.pages.get(this.page + 1)).css('display', 'block').css('left', this.options.PAGE_PADDING + this.options.PAGE_WIDTH);
            // trigger
            this._trigger('next');
        },
        
        /**
         * Flip to the previous page
         */
        previous: function(){
            // make sure next page can be moved
            if(this.options.use_ajax_loading && this.page + 2 >= this.state.length){
                this.addPage(this.state.length, this.options.BASE_URL.replace('[page#]', this.state.length + 1));
            }
            // make sure next page can be moved
            if(this.page - 1 < 0 || this.state.moving){
                return;
            }
            // set target
            this.state.target = 1;
            this.state.progress = 0;
            this.state.moving = true;
            this.state.previous_element = (this.page - 1 < 0) ? undefined : $(this.pages.get(this.page - 1)).css('display', 'block').css('left', -this.options.PAGE_PADDING - this.options.PAGE_WIDTH);
            this.state.current_element = $(this.pages.get(this.page)).css('display', 'block').css('left', 0);
                        // trigger
            this._trigger('previous');
        },
        
        /**
         * Render the book on the canvas
         */
        render: function() {
            if(!this.state.moving) return; 
            // set progress
            this.state.progress += (this.state.target - this.state.progress) * 0.3;
            // calculate displacement
            var dx = this.state.progress * this.options.PAGE_WIDTH;
            if(this.state.next_element == undefined && this.state.target < 0){
                dx = Math.max(dx, -0.33 * this.options.PAGE_WIDTH * this.state.progress); // if no next element exists, allow only 33% of blank to show 
            }
            else if(this.state.previous_element == undefined && this.state.target > 0){
                dx = Math.min(dx, 0.33 * this.options.PAGE_WIDTH * this.state.progress); // if no next element exists, allow only 33% of blank to show 
            }
            // move elements
            $(this.state.previous_element).css('left', dx - this.options.PAGE_PADDING - this.options.PAGE_WIDTH);
            $(this.state.current_element).css('left', dx);
            $(this.state.next_element).css('left', dx + this.options.PAGE_PADDING + this.options.PAGE_WIDTH );
            // check completion
            if(!this.state.dragging && this.state.moving && Math.abs(this.state.progress) > 0.997){
                // set new page
                this.page -= this.state.target;
                // final movement
                if(this.state.target > 0){
                    $(this.state.previous_element).css('display', 'block').css('left', 0);
                    $(this.state.next_element).css('display', 'none');
                }
                else if(this.state.target < 0){
                    $(this.state.previous_element).css('display', 'none');
                    $(this.state.next_element).css('display', 'block').css('left', 0);
                }
                $(this.state.current_element).css('display', 'none');
                // reset state
                this.state.moving = false;
                this.state.progress = 0;
                this.state.target = 0;
                // trigger event
                this._trigger('finished');
            }
        },
           
        /**
         * Start a page flip
         */
        _mouseDownHandler: function(event) {
            if(this.options.disabled) return;
            // make sure the mouse pointer is inside the book
            if (this.mouse.x < 0 || this.mouse.x > this.option.PAGE_WIDTH || this.mouse.y < 0 || this.mouse.y > this.options.PAGE_HEIGHT) {
                return;
            }
            // make sure it is not clicking a button
            if(event.target.tagName == 'BUTTON'){
                return;
            }
            // prevents text selection, image pulling
            if(event.preventDefault) event.preventDefault();
            // start drag
            this.state.target = 0;
            this.state.dragging = true;
            this.state.moving = true;
            this.state.drag_start = this.mouse.x;
            this.state.previous_element = (this.page - 1 < 0) ? undefined : $(this.pages.get(this.page - 1)).css('display', 'block').css('left', -this.options.PAGE_PADDING - this.options.PAGE_WIDTH);
            this.state.current_element = $(this.pages.get(this.page)).css('display', 'block').css('left', 0);
            this.state.next_element = (this.page + 1 >= this.pages.length) ? undefined : $(this.pages.get(this.page + 1)).css('display', 'block').css('left', this.options.PAGE_PADDING + this.options.PAGE_WIDTH);
            // add pages if getting close to the end of available pages
            if(this.options.use_ajax_loading && this.state.length < this.page + 2){
                this.addPage(this.state.length, this.options.BASE_URL.replace('[page#]', this.state.length + 1));
                this.addPage(this.state.length, this.options.BASE_URL.replace('[page#]', this.state.length + 1));
            }
            // run user-defined triggers
            this._trigger('started');
        },
        
        /**
         * Capture mouse movement in the book
         */
        _mouseMoveHandler: function(event){
            if(this.options.disabled) return;
            // set mouse position
            this.mouse.x = event.pageX - this.book.offset().left;
            this.mouse.y = event.pageY - this.book.offset().top;
            // actions while dragging
            if(this.state.dragging){
                // set state
                this.state.target = (this.mouse.x - this.state.drag_start)/this.options.PAGE_WIDTH;
            }
        },
        
        /**
         * Completes the page flip
         */
        _mouseUpHandler: function( event ) {
            if(this.options.disabled) return;
            // end drag
            this.state.dragging = false;
            // set target to move page if more than %20 of the page has moved
            var percent_moved = (this.mouse.x - this.state.drag_start)/this.options.PAGE_WIDTH;
            if(percent_moved > 0.20 && this.state.previous_element != undefined){
                this.state.target = 1;
            }
            else if(percent_moved < 0.20 && this.state.next_element != undefined){
                this.state.target = -1;
            }
            else{
                this.state.target = 0;
            }
        },
        
        /**
         * Display error messages; TODO: popup box
         */
        err: function(message){
            alert(message);
            if(window.console) console.log(message);
        },
        
        /**
         * Display warning messages
         */
        warn: function(message){
            if(window.console) console.log(message);
        },
  
        // Use the destroy method to clean up any modifications your widget has made to the DOM
        destroy: function() {
            this.element.empty();
            // In jQuery UI 1.8, you must invoke the destroy method from the base widget
            $.Widget.prototype.destroy.call( this );
        // In jQuery UI 1.9 and above, you would define _destroy instead of destroy and not call the base method
        }
    });
}( jQuery ) );

