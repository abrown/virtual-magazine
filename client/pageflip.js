/**
 * Page Flip jQuery Plugin, version 0.1
 * http://www.github.com/andrewsbrown/pageflip
 * 
 * This plugin uses the canvas element to simulate flipping through the pages
 * of a book. It modifies in large part the code given in the tutorial at
 * http://www.html5rocks.com/en/tutorials/casestudies/20things_pageflip/. It 
 * adds support for images on each page
 * 
 * Copyright 2012 Andrew Brown
 * Released under the ... license
 * http://jquery.org/license
 *
 * Date: 27 September 2012
 */
(function( $ ) {
    $.widget( "andrewsbrown.pageflip", {
        
        /**
         * Default options
         */
        options: { 
            BOOK_WIDTH: 830,        // dimensions of the whole book
            BOOK_HEIGHT: 260,       // dimensions of the whole book
            PAGE_WIDTH: 400,        // dimensions of a page in the book
            PAGE_HEIGHT: 250,       // dimensions of a page in the book
            CANVAS_PADDING: 60,     // the canvas size equals to the book dimensions + this padding
            SLICE_WIDTH: 3,         // for perspective, a flipped page is scaled using image slices of this width in pixels; less pixels per slice mean more slices
            FRAMES_PER_SECOND: 60,  // number of times per second the page flip is rendered
            BASE_URL: 'http://www.example.com/[page#].html?...', // URL to load pages from, the page number will replace "[page#]"; for this, pages are 1-indexed (i.e. start with 1, 2, 3...)
            use_ajax_loading: false,// uses the BASE_URL above to load pages into the book
            show_book_crease: true, // show book crease shadow overlay
            show_buttons: false     // show next/previous buttons
        },
        
        /**
         * By saving certain state variables in this object, we avoid repeating
         * calculations
         */
        state: {
            stress_rating: 0,       // the higher this goes, the more we degrade the canvas processing
            page_padding_x: 5,      // padding from the edge of the book to the edge of the page
            page_padding_y: 15,     // padding from the edge of the book to the edge of the page
            length: 0,              // number of pages loaded; this tracks how many pages have been added or are being added to the book. It is preferable to use this than this.pages.length so as not to duplicate AJAX calls to the same pages.
            ajax_load_failed: false // set to true when no more pages can be loaded
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
         * The current index of the page that is being flipped/interacted with;
         * if no pages are being flipped, it defaults to the left-most page
         */
        page: 0,
        
        /**
         * The canvas DOM element on which to display page flips
         */
        canvas: null,
        
        /**
         * The 2D context for the canvas
         */
        context: null,
	
        /**
         * The mouse position
         */
        mouse: {
            x: 0, 
            y: 0
        },
	
        /**
         * List of flip state objects, one per page; format of object:
         * {
         *       progress: [Number], // current progress of the flip (left -1 to right +1)
         *       target: [Number], // the target value towards which progress is always moving (left -1 to right +1)
         *       page: [DOMElement], // the page DOM element related to this flip
         *       dragging: [Boolean] // true while the page is being dragged
         *       moving: [Boolean] // true while the page is moving
         * }
         */
        flips: [],
 
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
                height: this.options.BOOK_HEIGHT,
                width: this.options.BOOK_WIDTH,
                position: 'relative'
            });
            
            // set state
            this.state.stress_rating = this.stress();
            this.warn('The stress rating for your browser is: '+this.state.stress_rating);
            if(this.state.stress_rating > 10){
                this.options.FRAMES_PER_SECOND = this.options.FRAMES_PER_SECOND / 3;
            }
            this.state.page_padding_x = (this.options.BOOK_WIDTH/2) - this.options.PAGE_WIDTH;
            this.state.page_padding_y = (this.options.BOOK_HEIGHT - this.options.PAGE_HEIGHT)/2;
            if( !this.options.BASE_URL.match(/\[page#\]/) ){
                this.warn('The option BASE_URL should contain the string [page#] for the widget to load pages from URLs.');
            }

            // get pages
            this.pages = this.book.find('.page');
            // add from URL
            if( this.options.use_ajax_loading ){
                this.addPage(2, this.options.BASE_URL.replace('[page#]', '3'));
                this.addPage(1, this.options.BASE_URL.replace('[page#]', '2'));
                this.addPage(0, this.options.BASE_URL.replace('[page#]', '1'));
                this.addPage(3, this.options.BASE_URL.replace('[page#]', '4'));
            }
            // add from DOM
            else{
                this.pages.each(function(i, el){
                    self.addPage(i, el);
                });
            }
            
            // create transparent shadow overlay
            if(this.options.show_book_crease){
                $('<div class="book-crease" />').prependTo(this.book).css({
                    'background-color': 'transparent',
                    'background-image': "url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAMgAAAABCAYAAACbv+HiAAAABmJLR0QA/wD/AP+gvaeTAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAB3RJTUUH3AkdFgUCjZnRbAAAAFhJREFUKM/tjjEKwDAMA08Z+of+/4/tUBp38RCMSeK9AiEs20IC5MwgwCb78W7lafN3506FLip2nWVbYA9qSa6ANmhLPAEHcPp8ATfwAK/nrxh79KTPjyI+gYgdAjV3R7UAAAAASUVORK5CYII=')",
                    'background-position': 'center top',
                    'background-repeat': 'repeat-y',
                    position: 'absolute',
                    top: (this.options.BOOK_HEIGHT - this.options.PAGE_HEIGHT)/2,
                    width: this.options.BOOK_WIDTH,
                    height: this.options.BOOK_HEIGHT - ((this.options.BOOK_HEIGHT - this.options.PAGE_HEIGHT)/2),
                    'z-index': 999
                });
            }
            
            // create buttons
            if(this.options.show_buttons){
                $('<button class="previous">&#9664;</button>').prependTo(this.book).css({
                    position: 'absolute',
                    top: (this.options.BOOK_HEIGHT)/2,
                    left: (this.state.page_padding_x)*2,
                    'z-index': 1001
                })
                .on('touchstart touchend touchcancel mousedown mouseup', function(e){
                    e.stopPropagation(); // we stop propagation on touches and mouse up/downs because it causes the page flip to render, messing up next()/previous(); we assume that a click event will also be triggered
                })
                .on('click', function(e){
                    e.stopPropagation();
                    self.previous();
                });
                $('<button class="next">&#9654;</button>').prependTo(this.book).css({
                    position: 'absolute',
                    top: (this.options.BOOK_HEIGHT)/2,
                    right: (this.state.page_padding_x)*2,
                    'z-index': 1001
                })
                .on('touchstart touchend touchcancel mousedown mouseup', function(e){
                    e.stopPropagation(); // we stop propagation on touches and mouse up/downs because it causes the page flip to render, messing up next()/previous(); we assume that a click event will also be triggered
                })
                .on('click', function(e){
                    e.stopPropagation();
                    self.next();
                });
            }
            
            // get canvas
            this.book.prepend('<canvas class="book-canvas" />');
            this.canvas = this.book.children('canvas:first').get(0);
            if( !this.canvas ) this.err('Could not find canvas element. The book element must contain one canvas as a child element.');
            // style canvas; resize the canvas to match the book size
            $(this.canvas).css({
                position: 'absolute', 
                'z-index': 1000,
                top: -this.options.CANVAS_PADDING,
                left: -this.options.CANVAS_PADDING
            });
            // resize the canvas to match the book size; see this article for why we cannot set width/height in CSS: http://www.informit.com/articles/article.aspx?p=1903884
            this.canvas.width = this.options.BOOK_WIDTH + ( this.options.CANVAS_PADDING * 2 );
            this.canvas.height = this.options.BOOK_HEIGHT + ( this.options.CANVAS_PADDING * 2 );
            // get context
            try{
                this.context = this.canvas.getContext('2d');
            }
            catch(error){
                this.err('The browser could not start the canvas element. Check that your browser supports the Canvas API.');
            }
            
            // start rendering
            setInterval( function(){
                self.render.call(self)
            }, 1000 / this.options.FRAMES_PER_SECOND );
            
            // set event handlers
            this.book.mousemove(function(e){
                self._mouseMoveHandler.call(self, e);
            });
            this.book.mousedown(function(e){
                self._mouseDownHandler.call(self, e);
            });
            this.book.mouseleave(function(e){
                self._mouseUpHandler.call(self, e);
            });
            this.book.mouseup(function(e){
                self._mouseUpHandler.call(self, e)
            });
            // set touch event handlers
            this.book.bind('touchstart', function(e){
                e.preventDefault(); // prevents touch from moving the screen around
                self._mouseMoveHandler.call(self, e.originalEvent.targetTouches[0]); // sets up this.mouse; otherwise the mouse down handler will not know where to start from
                self._mouseDownHandler.call(self, e.originalEvent.targetTouches[0]);
            });
            this.book.bind('touchmove', function(e){
                e.preventDefault(); // prevents touch from moving the screen around
                self._mouseMoveHandler.call(self, e.originalEvent.targetTouches[0]);
            });
            this.book.bind('touchend touchcancel', function(e){
                e.preventDefault(); // prevents touch from moving the screen around
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
                // add flip; @TODO simplify this
                var flip = this.newFlip(element, index);
                if(this.flips.length){
                    var added = false;
                    for(i in this.flips){
                        var id = parseInt($(this.flips[i].page).attr('id').replace('page#', ''));
                        if(index < id){
                            added = true;
                            this.flips.splice(i, 0, flip);
                            break;
                        }
                    }
                    if(!added) this.flips.push(flip);
                }
                else{
                    this.flips.push(flip);
                }
                // add ID, classes
                $(element).attr('id', 'page#'+index);
                $(element).addClass('page');
                $(element).addClass((index % 2) ? 'right-page' : 'left-page');
                // position pages; evens on the left, odds on the right
                $(element).css({
                    display: 'block',
                    width: this.options.PAGE_WIDTH,
                    height: this.options.PAGE_HEIGHT,
                    position: 'absolute', 
                    top: this.state.page_padding_y, 
                    left: (index % 2) ? this.options.PAGE_WIDTH + this.state.page_padding_x : this.state.page_padding_x,
                    overflow: 'hidden', // pages must fit in PAGE_WIDTH x PAGE_HEIGHT area
                    'background-color': '#fff' // by default, page backgrounds will be white
                });
                // reset z-indices
                for( var i = 0, len = this.pages.length; i < len; i++ ) {
                    this.pages[i].style.zIndex = len - i;
                }
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
         * Create a new flip object
         */
        newFlip: function(element, index, is_dragging){
            return {
                progress: (index % 2) ? 1 : -1,         // current progress of the flip (left == -1, right == +1)
                target: (index % 2) ? 1 : -1,           // the target value to move to (left == -1, right == +1)
                page: element,                          // the page DOM element related to this flip
                dragging: (is_dragging) ? true : false, // true while the page is being dragged
                moving: (is_dragging) ? true : false    // true while the page is moving
            };
        },
        
        /**
         * Flip to the next page
         */
        next: function(){
            var right_side_page = this.page + 1;
            var next_page = this.page + 2;
            // make sure next page can be moved
            if(this.options.use_ajax_loading && next_page + 2 >= this.state.length){
                this.addPage(this.state.length, this.options.BASE_URL.replace('[page#]', this.state.length + 1));
                this.addPage(this.state.length, this.options.BASE_URL.replace('[page#]', this.state.length + 1));
            }
            // make sure next page can be moved
            if(next_page >= this.pages.length){
                return;
            }
            // create flip
            this.flips[right_side_page].dragging = false;
            this.flips[right_side_page].moving = true;
            this.flips[right_side_page].progress = 1; // start from the right
            this.flips[right_side_page].target = -1; // move to the left
            // set page
            this.page += 2;
        },
        
        /**
         * Flip to the previous page
         */
        previous: function(){
            var left_side_page = this.page;
            var previous_page = this.page - 1;
            // make sure next page can be moved
            if(left_side_page - 1 < 0){
                return;
            }
            // create flip
            this.flips[left_side_page].dragging = false;
            this.flips[left_side_page].moving = true;
            this.flips[left_side_page].progress = -1; // start from the left
            this.flips[left_side_page].target = 1; // move to the right
            // set page
            this.page -= 2;
        },
        
        /**
         * Capture mouse movement in the book; offset mouse position so that
         * the top of the book spine is 0,0
         */
        _mouseMoveHandler: function(event){
            if(this.options.disabled) return;
            // set mouse position
            this.mouse.x = event.pageX - this.book.offset().left - ( this.options.BOOK_WIDTH / 2 );
            this.mouse.y = event.pageY - this.book.offset().top;
        },
        
        /**
         * Start a page flip
         */
        _mouseDownHandler: function( event ) {
            if(this.options.disabled) return;
            // make sure the mouse pointer is inside of the book
            if (Math.abs(this.mouse.x) > this.options.PAGE_WIDTH) {
                return;
            }
            // get the selected page, this is the index of the page that is
            // being flipped/interacted with
            this.page = (this.mouse.x > 0) ? this.page + 1 : this.page;
            if(this.page >= this.pages.length){
                this.page--;
            }
            // add pages if getting close to the end of available pages
            if(this.options.use_ajax_loading && this.state.length < this.page + 2){
                this.addPage(this.state.length, this.options.BASE_URL.replace('[page#]', this.state.length + 1));
                this.addPage(this.state.length, this.options.BASE_URL.replace('[page#]', this.state.length + 1));
            }
            // make sure the selected page can be moved
            if(this.page - 1 < 0 || this.page + 1 >= this.pages.length){
                this.page = (this.mouse.x > 0) ? this.page - 1 : this.page; // reset page
                return;
            }
            // create the flip
            this.flips[this.page].dragging = true;
            this.flips[this.page].moving = true;
            this.flips[this.page].progress = (this.page % 2) ? 1 : -1; // if odd, the page will start from the right (i.e. 1); if even, from the left (i.e. -1)
            // prevents the text selection
            if(event.preventDefault) event.preventDefault();
            // run user-defined triggers
            this._trigger('flipping');
        },
        
        /**
         * Completes the page flip
         */
        _mouseUpHandler: function( event ) {
            if(this.options.disabled) return;
            // cycle through flips, setting targets
            for( var i = 0; i < this.flips.length; i++ ) {
                // if this flip was being dragged, animate to its destination
                if( this.flips[i].dragging ) {
                    // set target
                    if( this.mouse.x < 0 ) {           
                        this.flips[i].target = -1;// finish moving left
                        this.page = (this.page % 2) ? this.page + 1 : this.page;
                    }
                    else {
                        this.flips[i].target = 1; // finish moving right
                        this.page = (this.page % 2) ? this.page - 1 : this.page - 2;
                    }
                }
                // stop dragging
                this.flips[i].dragging = false;
            }
        },
        
        /**
         * Render the book on the canvas
         */
        render: function() {
            // reset all pixels in the canvas
            this.context.clearRect( 0, 0, this.canvas.width, this.canvas.height );
            // check each flip for dragging
            for( var i = 0, len = this.flips.length; i < len; i++ ) {
                var flip = this.flips[i];
                if(flip.dragging) {
                    flip.target = Math.max( Math.min( this.mouse.x / this.options.PAGE_WIDTH, 1 ), -1 ); // determine whether to move towards mouse or page edge
                }
                // ease progress towards the target value 
                if(this.state.stress_rating > 10){
                    flip.progress += (flip.target - flip.progress) * 0.6;
                }
                else{
                    flip.progress += (flip.target - flip.progress) * 0.3;
                }
                // if the flip is being dragged or is somewhere in the middle of the book, render it
                if(flip.dragging || Math.abs(flip.progress) < 0.997) {
                    this._drawFlip(flip);
                }
                // flip complete
                if(Math.abs(flip.progress) > 0.997 && flip.moving){
                    flip.moving = false;
                    // trigger event
                    this._trigger('flipped');
                }               
            }	
        },
	
        /**
         * Draw the page flip
         */
        _drawFlip: function( flip ) {
            var o = this.options;
            // strength of the fold is strongest in the middle of the book; rounded to avoid error in rgba strings
            var strength = Math.round((1 - Math.abs(flip.progress)) * 1000) / 1000;
            // width of the folded paper
            var foldWidth = ( o.PAGE_WIDTH * 0.5 ) * ( 1 - flip.progress );
            // X position of the folded paper
            var foldX = o.PAGE_WIDTH * flip.progress + foldWidth;
            // how far the page should outdent vertically due to perspective
            var verticalOutdent = 20 * strength;
            // the maximum width of the left and right side shadows
            var paperShadowWidth = ( o.PAGE_WIDTH * 0.5 ) * Math.max( Math.min( 1 - flip.progress, 0.5 ), 0 );
            var rightShadowWidth = ( o.PAGE_WIDTH * 0.5 ) * Math.max( Math.min( strength, 0.5 ), 0 );
            var leftShadowWidth = ( o.PAGE_WIDTH * 0.5 ) * Math.max( Math.min( strength, 0.5 ), 0 );
            
            // change page element width to match the x position of the fold
            var index = this._getPageIndex(flip.page);
            var left_side_page = (index % 2) ? index - 1 : index - 2;
            var right_side_page = (index % 2) ? index : index - 1;
            $(this.pages.get(left_side_page)).width(Math.min(o.PAGE_WIDTH + foldX - foldWidth, o.PAGE_WIDTH)); // left side page 
            $(this.pages.get(right_side_page)).width(Math.max(foldX, 0)); // right side page

            // set up this.context
            this.context.save();
            this.context.translate( o.CANVAS_PADDING + ( o.BOOK_WIDTH / 2 ), ((this.options.BOOK_HEIGHT - this.options.PAGE_HEIGHT)/2) + o.CANVAS_PADDING );
            
            // draw a sharp shadow on the left side of the page
            this.context.strokeStyle = 'rgba(0,0,0,'+(0.05 * strength)+')';
            this.context.lineWidth = 30 * strength;
            this.context.beginPath();
            this.context.moveTo(foldX - foldWidth, -verticalOutdent * 0.5);
            this.context.lineTo(foldX - foldWidth, o.PAGE_HEIGHT + (verticalOutdent * 0.5));
            this.context.stroke();

            // draw the right side drop shadow
            var rightShadowGradient = this.context.createLinearGradient(foldX, 0, foldX + rightShadowWidth, 0);
            rightShadowGradient.addColorStop(0, 'rgba(0,0,0,'+(strength*0.2)+')');
            rightShadowGradient.addColorStop(0.8, 'rgba(0,0,0,0.0)');
            this.context.fillStyle = rightShadowGradient;
            this.context.beginPath();
            this.context.moveTo(foldX, 0);
            this.context.lineTo(foldX + rightShadowWidth, 0);
            this.context.lineTo(foldX + rightShadowWidth, o.PAGE_HEIGHT);
            this.context.lineTo(foldX, o.PAGE_HEIGHT);
            this.context.fill();
		
            // draw the left side drop shadow
            var leftShadowGradient = this.context.createLinearGradient(foldX - foldWidth - leftShadowWidth, 0, foldX - foldWidth, 0);
            leftShadowGradient.addColorStop(0, 'rgba(0,0,0,0.0)');
            leftShadowGradient.addColorStop(1, 'rgba(0,0,0,'+(strength*0.15)+')');
            this.context.fillStyle = leftShadowGradient;
            this.context.beginPath();
            this.context.moveTo(foldX - foldWidth - leftShadowWidth, 0);
            this.context.lineTo(foldX - foldWidth, 0);
            this.context.lineTo(foldX - foldWidth, o.PAGE_HEIGHT);
            this.context.lineTo(foldX - foldWidth - leftShadowWidth, o.PAGE_HEIGHT);
            this.context.fill();
		
            // draw the gradient applied to the folded paper (highlights & shadows)
            var foldGradient = this.context.createLinearGradient(foldX - paperShadowWidth, 0, foldX, 0);
            foldGradient.addColorStop(0.35, "rgba(0, 0, 0, 0.01)"); // '#fafafa');
            foldGradient.addColorStop(0.73, "rgba(0, 0, 0, 0.08)"); //'#eeeeee'); 
            foldGradient.addColorStop(0.9, "rgba(0, 0, 0, 0.01)"); //'#fafafa');
            foldGradient.addColorStop(1.0, "rgba(0, 0, 0, 0.1)"); //'#e2e2e2'); 
            this.context.fillStyle = foldGradient;
            this.context.strokeStyle = 'rgba(0,0,0,0.06)';
            this.context.lineWidth = 0.5;
                
            // setup the folded piece of paper
            this.context.beginPath();
            this.context.moveTo(foldX, 0); // top right
            this.context.lineTo(foldX, o.PAGE_HEIGHT); // bottom right
            this.context.quadraticCurveTo(foldX, o.PAGE_HEIGHT + (verticalOutdent * 2), foldX - foldWidth, o.PAGE_HEIGHT + verticalOutdent); // bottom curve, right to left
            this.context.lineTo(foldX - foldWidth, -verticalOutdent); // top left
            this.context.quadraticCurveTo(foldX, -verticalOutdent * 2, foldX, 0); // top curve, left to right
            this.context.clip(); // clip the sliced image inside this path
			
            // slice image into folded page
            var page_to_paint = (index % 2) ? index + 1 : index;
            var img = this._getPageImage(this.pages.get(page_to_paint));
            if( img ){
                var numSlices = Math.ceil(foldWidth/o.SLICE_WIDTH);
                var imgSliceWidth = img.width * (o.SLICE_WIDTH / o.PAGE_WIDTH);
                for(var i = 0; i < numSlices; i++){
                    // calculate slice dimensions from source image
                    var sx = imgSliceWidth * i;
                    var sy = 0;
                    var sw = imgSliceWidth;
                    var sh = img.height;
                    // calculate slice dimensions on the canvas
                    var dx = Math.floor((foldX - foldWidth) + (i * o.SLICE_WIDTH));
                    var dy = this._pointAt(i/numSlices, -verticalOutdent, -verticalOutdent * 2, -verticalOutdent); // t, p1, p2, p3
                    var dw = o.SLICE_WIDTH;
                    var dh = this._pointAt(i/numSlices, o.PAGE_HEIGHT + verticalOutdent, o.PAGE_HEIGHT + (verticalOutdent * 2), o.PAGE_HEIGHT + verticalOutdent) - dy; // t, p1, p2, p3
                    // draw slice
                    this.context.drawImage(img, sx, sy, sw, sh, dx, dy, dw, dh);
                }
            }
            // or draw a white page
            else{
                var foldGradient = this.context.createLinearGradient(foldX - paperShadowWidth, 0, foldX, 0);
                foldGradient.addColorStop(0.35, '#fafafa');
                foldGradient.addColorStop(0.73, '#eeeeee'); 
                foldGradient.addColorStop(0.9, '#fafafa');
                foldGradient.addColorStop(1.0, '#e2e2e2'); 
                this.context.fillStyle = foldGradient;
            }
            
            // draw the folded piece of paper
            this.context.fill();
            this.context.stroke();
		
            // restore state
            this.context.restore();
        },
               
        /**
         * Return an image for the given page element; TODO: if the element
         * has no image, create one...
         */
        _getPageImage: function(element){            
            var img = new Image();
            // get first page image
            var _img = $(element).find('img:first');
            if(_img.length){
                img.src = _img.attr('src');
                return img;
            }
            // return
            return null;
        },
        
        _getPageIndex: function(element){
            return parseInt($(element).attr('id').replace('page#', ''));
        },
        
        /**
         * Return if at least one page is being dragged; used by the mouse handler
         * to increment page count
         */
        /*
        _multipleDragged: function(){
            for( var i = 0, len = this.flips.length; i < len; i++ ) {
                if( this.flips[i].dragging ) return true;
            }
            return false;
        },
        */
	
        /**
         * Calculates a point on a quadratic bezier curve based on a t-value;
         * see http://en.wikipedia.org/wiki/B%C3%A9zier_curve#Quadratic_curves
         */
        _pointAt: function(t, p1, p2, p3){
            return (Math.pow((1-t), 2) * p1) + (2 * (1-t) * t * p2) + (Math.pow(t, 2) * p3);
        },
	
        /**
         * Debugging function: draws a 3x3 red square
         */
        _drawPoint: function (x, y){
            this.context.save();
            this.context.fillStyle = 'rgba(255,0,0,1)';
            this.context.fillRect(x - 1, y - 1, 3, 3);
            this.context.restore();
        },
        
        /**
         * Get stress rating for the client; this determines how much canvas
         * processing to perform
         */
        stress: function(){
            var start = +new Date();              
            for (var i=0, j=1; i<1000000; i++) j++; 
            var end = +new Date();          
            return end - start;
        },
        
        /**
         * Display error messages; TODO: popup box
         */
        err: function(message){
            alert(message);
            if(window.console) console.log(message);
        },
        
        warn: function(message){
            if(window.console) console.log(message);
        },
        
        /**
         * Use the destroy method to clean up any modifications your widget has made to the DOM
         */
        destroy: function() {
            this.element.empty();
            // In jQuery UI 1.8, you must invoke the destroy method from the base widget
            $.Widget.prototype.destroy.call( this );
        // In jQuery UI 1.9 and above, you would define _destroy instead of destroy and not call the base method
        }
    });
}( jQuery ) );

