/**
 * Page Flip jQuery Plugin, version 0.1
 * http://www.github.com/andrewsbrown/page-flip
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
            PAGE_Y: 5, // vertical spacing between the top edge of the book and the papers
            CANVAS_PADDING: 60, // the canvas size equals to the book dimensions + this padding
            SLICE_WIDTH: 3, // for perspective, a flipped page is scaled using image slices of this width in pixels; less pixels per slice mean more slices
            FRAMES_PER_SECOND: 60 // number of times per second the page flip is rendered
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
         * The current page number
         */
        page: 0,
        
        /**
         * The canvas element on which to display page flips
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
         * List of flip state objects, one per page
         */
        flips: [],
 
        /**
         * Constructor
         */
        _create: function() {
            // get book
            this.book = $(this.element);
            if( !this.book ) this.err('Could not find book element.');
            if( !this.book.hasClass('book') ) this.book.addClass('book');
            // get pages
            this.pages = this.book.children('.page');
            if( this.pages.length <= 0 ) this.err('Could not find page elements. Page elements must be designated with a "page" class.');
            // hide later pages underneath, create flip definitions
            for( var i = 0, len = this.pages.length; i < len; i++ ) {
                this.pages[i].style.zIndex = len - i;
                this.flips.push({
                    progress: 1, // current progress of the flip (left -1 to right +1)
                    target: 1, // the target value towards which progress is always moving
                    page: this.pages[i], // the page DOM element related to this flip
                    dragging: false // true while the page is being dragged
                });
            }
            // get canvas
            this.canvas = this.book.children('canvas:first').get(0);
            if( !this.canvas ) this.err('Could not find canvas element. The book element must contain one canvas as a child element.');
            // resize the canvas to match the book size
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
        },
        
        /**
         * Capture mouse movement in the book; offset mouse position so that
         * the top of the book spine is 0,0
         */
        _mouseMoveHandler: function(event){
            this.mouse.x = event.pageX - this.book.offset().left - ( this.options.BOOK_WIDTH / 2 );
            this.mouse.y = event.pageY - this.book.offset().top;
            //this.mouse.x = event.clientX - this.book.get(0).offsetLeft - ( this.options.BOOK_WIDTH / 2 );
            //this.mouse.y = event.clientY - this.book.get(0).offsetTop;
        },
        
        /**
         * Start a page flip
         */
        _mouseDownHandler: function( event ) {
            // make sure the mouse pointer is inside of the book
            if (Math.abs(this.mouse.x) < this.options.PAGE_WIDTH) {
                if (this.mouse.x < 0 && this.page - 1 >= 0) {
                    // we are on the left side, drag the previous page
                    this.flips[this.page - 1].dragging = true;
                }
                else if (this.mouse.x > 0 && this.page + 1 < this.flips.length) {
                    // we are on the right side, drag the current page
                    this.flips[this.page].dragging = true;
                }
            }    
            // prevents the text selection
            event.preventDefault();
            // run user-defined triggers
            this._trigger('started');
        },
	
        /**
         * Completes the page flip
         */
        _mouseUpHandler: function( event ) {
            for( var i = 0; i < this.flips.length; i++ ) {
                // If this flip was being dragged, animate to its destination
                if( this.flips[i].dragging ) {
                    // Figure out which page we should navigate to
                    if( this.mouse.x < 0 ) {
                        this.flips[i].target = -1;
                        this.page = Math.min( this.page + 1, this.flips.length );
                    }
                    else {
                        this.flips[i].target = 1;
                        this.page = Math.max( this.page - 1, 0 );
                    }
                }
                this.flips[i].dragging = false;
            }
            // run user-defined triggers
            this._trigger('stopped');
        },
        
        /**
         * Display error messages; TODO: popup box
         */
        err: function(message){
            console.log(message);
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
                flip.progress += ( flip.target - flip.progress ) * 0.2;
                // If the flip is being dragged or is somewhere in the middle of the book, render it
                if( flip.dragging || Math.abs( flip.progress ) < 0.997 ) {
                    this._drawFlip( flip );
                }	
            }	
        },
	
        /**
         * Draw the page flip
         */
        _drawFlip: function( flip ) {
            var o = this.options;
            // strength of the fold is strongest in the middle of the book
            var strength = 1 - Math.abs( flip.progress );
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
            flip.page.style.width = Math.max(foldX, 0) + "px";

            // set up this.context
            this.context.save();
            this.context.translate( o.CANVAS_PADDING + ( o.BOOK_WIDTH / 2 ), o.PAGE_Y + o.CANVAS_PADDING );      
		
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
            var img = this._getPageImage(flip.page);
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
            var _img = $(element).find('img:first');
            var img = new Image();
            img.src = _img.attr('src');
            return img;
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

