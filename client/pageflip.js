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
            BOOK_WIDTH: 830, // dimensions of the whole book
            BOOK_HEIGHT: 260, // dimensions of the whole book
            PAGE_WIDTH: 400, // dimensions of a page in the book
            PAGE_HEIGHT: 250, // dimensions of a page in the book
            CANVAS_PADDING: 60, // the canvas size equals to the book dimensions + this padding
            SLICE_WIDTH: 3, // for perspective, a flipped page is scaled using image slices of this width in pixels; less pixels per slice mean more slices
            FRAMES_PER_SECOND: 60 // number of times per second the page flip is rendered
        },
        
        /**
         * By saving certain state variables in this object, we avoid repeating
         * calculations
         */
        state: {
            stress_rating: 0 // the higher this goes, the more we degrade the canvas processing
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
         * The current page numer of the left-most page; this counter starts
         * at 0.
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
            // get client stress rating
            this.state.stress_rating = this.stress();
            if(this.state.stress_rating > 10){
                this.options.FRAMES_PER_SECOND = this.options.FRAMES_PER_SECOND / 3;
            }
            
            // get book
            this.book = $(this.element);
            if( !this.book ) this.err('Could not find book element.');
            if( !this.book.hasClass('book') ) this.book.addClass('book');
            // style book
            this.book.width(this.options.BOOK_WIDTH);
            this.book.height(this.options.BOOK_HEIGHT);
            this.book.css({
                position: 'relative'
            });
            
            // get pages
            this.pages = this.book.children('.page');
            if( this.pages.length <= 0 ) this.err('Could not find page elements. Page elements must be designated with a "page" class.');
            // hide later pages underneath, create flip definitions
            for( var i = 0, len = this.pages.length; i < len; i++ ) {
                var page = this.pages[i];
                page.style.zIndex = len - i;
                this.flips.push({
                    progress: 1, // current progress of the flip (left -1 to right +1)
                    target: 1, // the target value towards which progress is always moving
                    page: page, // the page DOM element related to this flip
                    dragging: false, // true while the page is being dragged
                    moving: false // true while the page is moving
                });
                // position pages; evens on the left, odds on the right
                var _PAGE_X = (this.options.BOOK_WIDTH/2) - this.options.PAGE_WIDTH;
                var PAGE_X = (i%2) ? _PAGE_X + this.options.PAGE_WIDTH : _PAGE_X;
                var PAGE_Y = (this.options.BOOK_HEIGHT - this.options.PAGE_HEIGHT)/2;
                $(page).css({
                    position: 'absolute', 
                    top: PAGE_Y+'px', 
                    left: PAGE_X+'px'
                });
                // wrap page contents
                $(page).wrapInner('<div class="page-wrapper" />');
            }
            // style pages
            $('.page').css({
                display: 'block',
                width: this.options.PAGE_WIDTH,
                height: this.options.PAGE_HEIGHT,
                overflow: 'hidden',  
                'background-color': '#fff'
            });
            // style page wrappers
            $('.page-wrapper').css({
                display: 'block',
                width: this.options.PAGE_WIDTH,
                height: this.options.PAGE_HEIGHT
            });
            
            // create transparent shadow overlay
            this.book.prepend('<div class="page-shadow" />');
            $('.page-shadow').css({
                'background-color': 'transparent',
                //'background-image': "url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAMgAAAABCAYAAACbv+HiAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsQAAA7EAZUrDhsAAAB+SURBVDhP7VFBCsAgDGtVEPH/j/MzOiNEiisbu08o1bRNQtXe+5B5xljpdjz8C6aqm9vOEX/C0ENv7LN8rFkuT8/TsLMeDxcBvrkjQUaEEFYJ2WK8n56hjXnG+QZOvtaapJSk1iqlFMk5L40Y49aGrg16op8z04/7uT/4uoELOe91/kBXzBMAAAAASUVORK5CYII=')",
                'background-image': "url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAMgAAAABCAYAAACbv+HiAAAABmJLR0QA/wD/AP+gvaeTAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAB3RJTUUH3AkdFgUCjZnRbAAAAFhJREFUKM/tjjEKwDAMA08Z+of+/4/tUBp38RCMSeK9AiEs20IC5MwgwCb78W7lafN3506FLip2nWVbYA9qSa6ANmhLPAEHcPp8ATfwAK/nrxh79KTPjyI+gYgdAjV3R7UAAAAASUVORK5CYII=')",
                'background-position': 'center top',
                'background-repeat': 'repeat-y',
                position: 'absolute',
                top: (this.options.BOOK_HEIGHT - this.options.PAGE_HEIGHT)/2,
                width: this.options.BOOK_WIDTH,
                height: this.options.BOOK_HEIGHT,
                'z-index': 100
            });
            
            // get canvas
            this.book.prepend('<canvas class="book-canvas" />');
            this.canvas = this.book.children('canvas:first').get(0);
            if( !this.canvas ) this.err('Could not find canvas element. The book element must contain one canvas as a child element.');
            // style canvas; resize the canvas to match the book size
            $(this.canvas).css({
                position: 'absolute', 
                'z-index': 1000
            });
            this.canvas.width = this.options.BOOK_WIDTH + ( this.options.CANVAS_PADDING * 2 );
            this.canvas.height = this.options.BOOK_HEIGHT + ( this.options.CANVAS_PADDING * 2 );
            // offset the canvas so that its padding is evenly spread around the book
            this.canvas.style.top = -this.options.CANVAS_PADDING + "px";
            this.canvas.style.left = -this.options.CANVAS_PADDING + "px";
            // get context
            try{
                this.context = this.canvas.getContext('2d');
            }
            catch(error){
                this.err('The browser could not start the canvas element. Check that your browser supports the Canvas API.');
            }
            
            // start rendering
            var self = this;
            setInterval( function(){
                self.render.call(self)
            }, 1000 / this.options.FRAMES_PER_SECOND );
            
            // set event handlers
            this.book.mousemove(function(e){
                self._mouseMoveHandler.call(self, e)
            });
            this.book.mousedown(function(e){
                self._mouseDownHandler.call(self, e)
            });
            this.book.mouseup(function(e){
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
         * Capture mouse movement in the book; offset mouse position so that
         * the top of the book spine is 0,0
         */
        _mouseMoveHandler: function(event){
            if(this.options.disabled) return;
            this.mouse.x = event.pageX - this.book.offset().left - ( this.options.BOOK_WIDTH / 2 );
            this.mouse.y = event.pageY - this.book.offset().top;
        },
        
        /**
         * Start a page flip
         */
        _mouseDownHandler: function( event ) {
            if(this.options.disabled) return;
            // make sure the mouse pointer is inside of the book
            if (Math.abs(this.mouse.x) < this.options.PAGE_WIDTH) {
                if (this.mouse.x < 0 && this.page - 1 >= 0) {
                    // we are on the left side, drag the left page
                    this.flips[this.page].dragging = true;
                    this.flips[this.page].moving = true;
                    this.flips[this.page].progress = -1;
                    this.flips[this.page].image_on_page = this.page;
                    // switch page to the one underneath
                    this.page -= 2;
                }
                else if (this.mouse.x > 0 && this.page + 2 < this.flips.length) {
                    // we are on the right side, drag the right page
                    this.flips[this.page + 1].dragging = true;
                    this.flips[this.page + 1].moving = true;
                    this.flips[this.page + 1].progress = 1;
                    this.flips[this.page + 1].image_on_page = this.page + 2;
                }
            }    
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
            for( var i = 0; i < this.flips.length; i++ ) {
                // if this flip was being dragged, animate to its destination
                if( this.flips[i].dragging ) {
                    // figure out which page we should navigate to
                    if( this.mouse.x < 0 ) {
                        // moving left
                        this.flips[i].target = -1;
                    }
                    else {
                        // moving right
                        this.flips[i].target = 1;
                    }
                }
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
                if( flip.dragging ) {
                    flip.target = Math.max( Math.min( this.mouse.x / this.options.PAGE_WIDTH, 1 ), -1 );
                }
                // ease progress towards the target value 
                if(this.state.stress_rating > 10){
                    flip.progress += ( flip.target - flip.progress ) * 0.6;
                }
                else{
                    flip.progress += ( flip.target - flip.progress ) * 0.2;
                }
                
                // if the flip is being dragged or is somewhere in the middle of the book, render it
                if( flip.dragging || Math.abs( flip.progress ) < 0.997 ) {
                    this._drawFlip( flip );
                }
                // flip complete
                if(Math.abs( flip.progress ) > 0.997 && flip.moving){
                    flip.moving = false;
                    // set page
                    if( flip.target == -1 ){
                        this.page += 2;
                    }
                    // ensure pages are completely set to proper size
                    if(this.page-2 >= 0) $(this.pages.get(this.page-2)).width(0);
                    if(this.page-1 >= 0) $(this.pages.get(this.page-1)).width(0);
                    $(this.pages.get(this.page)).width(this.options.PAGE_WIDTH);
                    $(this.pages.get(this.page+1)).width(this.options.PAGE_WIDTH);
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
            $(this.pages.get(this.page)).width(Math.min(o.PAGE_WIDTH + foldX - foldWidth, o.PAGE_WIDTH));
            $(this.pages.get(this.page + 1)).width(Math.max(foldX, 0));

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
		
            if( this.state.stress_rating < 10 ){
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
            }
                
            // setup the folded piece of paper
            this.context.beginPath();
            this.context.moveTo(foldX, 0); // top right
            this.context.lineTo(foldX, o.PAGE_HEIGHT); // bottom right
            this.context.quadraticCurveTo(foldX, o.PAGE_HEIGHT + (verticalOutdent * 2), foldX - foldWidth, o.PAGE_HEIGHT + verticalOutdent); // bottom curve, right to left
            this.context.lineTo(foldX - foldWidth, -verticalOutdent); // top left
            this.context.quadraticCurveTo(foldX, -verticalOutdent * 2, foldX, 0); // top curve, left to right
            this.context.clip(); // clip the sliced image inside this path
			
            // slice image into folded page
            var img = this._getPageImage(this.flips[flip.image_on_page].page);
            if(img && this.state.stress_rating < 10 ){
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
        
        /**
         * Return if at least one page is being dragged; used by the mouse handler
         * to increment page count
         */
        _multipleDragged: function(){
            for( var i = 0, len = this.flips.length; i < len; i++ ) {
                if( this.flips[i].dragging ) return true;
            }
            return false;
        },
	
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

