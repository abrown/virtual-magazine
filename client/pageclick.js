(function( $ ) {
    $.widget( "andrewsbrown.pageview", {
        
        /**
         * Default options
         */
        options: { 
            url: "...",
            showNavigation: false,
            autoresizeContainer: true,
            fixedWidth: false, // false or integer number of pixels--server will only produce image sizes divisible by 50; not compatible with fixedHeight.
            fixedHeight: false, // false or integer number of pixels--server will only produce image sizes divisible by 50; not compatible with fixedWidth.
            editable: false
        },
        
        magazine: null,  
 
        /**
         * Constructor
         */
        _create: function() {
            // get magazine data
            this.magazine = this.getMagazine();
            // start pageflip widget
            this.element.pageflip({
                BOOK_WIDTH: 1200,
                PAGE_WIDTH: 1100,
            });
            var self = this, o = this.options, el = this.element;
            // add class
            el.addClass('magazine-container');
            // create initial data store
            el.data('page', 1);
            el.data('magazine', null);
            el.data('magazine.id', null);
            el.data('navigation-controls', null);
            el.data('editing-controls', null);
            el.data('editing-link', null);
            el.data('editing-link-dialog', null);
            el.data('social-settings');
            // get magazine
            self.getMagazine();
            // display navigation
            if( o.showNavigation ){
                self.displayNavigationControls();
            }
            // display editing controls
            if( o.editable ){
                self.displayEditingControls();
            }
            // disable image dragging
            $('.magazine-container img').live('dragstart', function(event){
                return false;
            });
            // disable caching; IE does not perform HTTP GET on every AJAX call
            $.ajaxSetup({
                cache: false
            });
            // enable key actions
            el.keypress(function(event){
                if(event.which == 39 || event.which == 40){
                    self.next();
                }
                else if( event.which == 37 || event.which == 38){
                    self.previous();
                }
            });
            // set dimensions
            if(o.fixedWidth){
                self.setPageWidth(o.fixedWidth);
            }
            else if(o.fixedHeight){
                self.setPageHeight(o.fixedHeight);
            }
        /*
            else if(o.autoresizeContainer){
                // resize image
                if( el.height() > el.width() ){
                    var width = Math.floor(el.width() / 50) * 50;
                    self.setPageWidth(width);
                }
                else{
                    var height = Math.floor(el.height() / 50) * 50;
                    self.setPageHeight(height);
                }
            }
            */
        },
        
        /**
         * Retrieve magazine data as JSON from the server;
         * start page display
         */
        getMagazine: function(){
            var self = this, o = this.options, el = this.element;
            // retrieve magazine JSON with AJAX
            $.ajax(o.url, {
                data: {
                    accept: 'application/json' // ensures the request will return JSON from the server
                },
                accept: 'application/json', // sets Accept: header
                dataType: 'json',
                success: function(data, status, xhr){
                    // verify data
                    try{
                        if( typeof data != 'object' ) throw "No object returned.";
                        if( typeof data.id != 'string' ) throw "No ID specified";
                        if( typeof data.pages != 'object' ) throw "No page URLs provided.";
                        if( typeof data.url_to_pdf != 'string' ) throw "No PDF link provided.";
                    }
                    catch(e){
                        self.displayError("Invalid Data", e);
                    }
                    // store magazine data
                    el.data('magazine', data);
                    // store shortcut to ID
                    el.data('magazine.id', data.id);
                    // display title/created date
                    if( o.showNavigation ){
                        el.data('navigation-controls').find('h2').text(el.data('magazine').title);
                        el.data('navigation-controls').find('.magazine-created').text(el.data('magazine').created);
                    }
                    // callback
                    self._trigger('magazine_retrieved');
                    // start page display
                    self.displayPage(el.data('page'));
                }, 
                error: function(xhr, status, error){
                    var body = '<pre>URL: '+o.url+'</pre>';
                    body += '<pre>Status: '+xhr.status+' '+xhr.statusText+'</pre>';
                    body += '<pre>Body: <code>'+xhr.responseText+'</code></pre>';
                    self.displayError('Failed to Retrieve Magazine', body);
                }
            });
            // return
            return el.data('magazine');
        },
           
        /**
         * Retrieve social settings; triggers activateSocialSettings(), which
         * sets up event handlers for available links.
         */
        getSocialSettings: function(){
            var self = this, o = this.options, el = this.element;
            // retrieve social settings JSON with AJAX
            var url = o.url.replace(/service\.php\/magazine.+/, 'service.php/social');
            $.ajax(url, {
                data: {
                    accept: 'application/json' // ensures the request will return JSON from the server
                },
                accept: 'application/json', // sets Accept: header
                dataType: 'json',
                success: function(data, status, xhr){
                    // verify data
                    try{
                        if( typeof data != 'object' ) throw "No object returned.";
                        for(var property in data){
                            if(typeof data[property] != 'string'){
                                throw "Property '"+property+"' must be a string.";
                            }
                        }
                    }
                    catch(e){
                        self.displayError("Invalid Data", e);
                    }
                    // store social settings
                    el.data('social-settings', data);
                    // callback
                    self._trigger('social_settings_retrieved');
                    // start page display
                    if(o.showNavigation){
                        self.activateSocialSettings();
                    }
                }, 
                error: function(xhr, status, error){
                    var body = '<pre>URL: '+o.url+'</pre>';
                    body += '<pre>Status: '+xhr.status+' '+xhr.statusText+'</pre>';
                    body += '<pre>Body: '+xhr.responseText+'</pre>';
                    self.displayError('Failed to Retrieve Magazine', body);
                }
            });
        },
        
        /**
         * Retrive page links using the OPTIONS method of the Page resource
         */
        getPageLinks: function(){
            var self = this, o = this.options, el = this.element;
            // create url
            var url = o.url.replace(/service\.php\/magazine.+/, 'service.php/page/');
            url += el.data('magazine.id')+'/'+el.data('page');
            // make AJAX call
            $.ajax(url, {
                data: {
                    method: 'OPTIONS', // this method is overloaded to display page links
                    accept: 'text/html'
                },
                dataType: 'html',
                success: function(data, status, xhr){
                    var links = el.data('editing-controls').find('.magazine-editing-links:first');
                    // callback
                    self._trigger('page_links_retrieved');
                    // add new link HTML 
                    links.empty();
                    links.append(data);
                }, 
                error: function(xhr, status, error){
                    var body = '<pre>URL: '+url+'</pre>';
                    body += '<pre>Status: '+xhr.status+' '+xhr.statusText+'</pre>';
                    body += '<pre>Body: '+xhr.responseText+'</pre>';
                    self.displayError('Failed to Retrieve Page', body);
                }
            });
        },
        
        /**
         * Retrieve embed code
         */
        getEmbed: function(){
            var self = this, o = this.options, el = this.element;
            // create url
            var url = o.url.replace('service.php/magazine', 'embed.php');
            // make AJAX call
            $.ajax(url, {
                data:{
                    accept: 'text/html'
                },
                dataType: 'html',
                success: function(data, status, xhr){
                    // callback
                    self._trigger('embed_code_retrieved');
                    // display dialog
                    var dialog = $('<div class="ui-dialog"></div>');
                    dialog.append('<pre>'+data+'</pre>');
                    dialog.dialog({
                        autoOpen: false,
                        title: 'Embed Code',
                        modal: true,
                        width: $(window).width() / 2,
                        buttons: {
                            "Ok": function(){
                                $(this).dialog("close");    
                            }
                        }
                    }); 
                    // display dialog
                    dialog.dialog('open');
                }, 
                error: function(xhr, status, error){
                    var body = '<pre>URL: '+url+'</pre>';
                    body += '<pre>Status: '+xhr.status+' '+xhr.statusText+'</pre>';
                    body += '<pre>Body: '+xhr.responseText+'</pre>';
                    self.displayError('Failed to Retrieve Page', body);
                }
            });
        },
        
        /**
         * Display page
         */
        displayPage: function(i){
            var self = this, o = this.options, el = this.element;
            // validate
            try{
                if( typeof i != 'number' ) throw "Page must be an integer.";
                if( typeof el.data('magazine').pages[i-1] != 'string') throw "No page URL found in magazine data.";
            }
            catch(e){
                self.displayError("Invalid Page", e);
                return;
            }
            // empty container
            if( el.data('page') != 1 ) el.css('height', el.height());
            el.empty();
            // retrieve page as text/html
            var settings = {
                dataType: 'html',
                success: function(data, status, xhr){
                    // add page HTML
                    el.append(data);
                    // re-add previous/next buttons
                    self.displayPreviousNext();
                    // resize to image height/width
                    if( o.autoresizeContainer ){
                        el.find('.magazine-page img:first').load(function(){
                            el.width($(this).width());
                            el.height($(this).height());
                            /*
                            // resize height if necessary
                            if( o.fixedHeight ){
                                el.height($(this).height());
                            }
                            // resize width if necessary
                            if( o.fixedWidth ){
                                el.width($(this).width());

                            }
                            */
                            // resize navigation controls
                            if(o.showNavigation){
                                el.data('navigation-controls').width($(this).width());
                            }
                            // resize editing controls
                            if(o.editable){
                                el.data('editing-controls').width($(this).width());
                            }
                            // always resize previous/next buttons
                            el.find('.magazine-previous, .magazine-next').height(el.height());
                        });
                    }
                    // IE hack: IE won't register clicks on transparent backgrounds, so...
                    if( $.browser.msie ){
                        $('.magazine-link').css('background-color', '#fff');
                        $('.magazine-link').css('filter', 'alpha(opacity=1)');
                    }
                    // callback
                    self._trigger('page_displayed');
                }, 
                error: function(xhr, status, error){
                    var body = 'URL: <code>'+o.url+'</code><br/>';
                    body += 'Status: <code>'+xhr.status+' '+xhr.statusText+'</code><br/>';
                    body += 'Body: <code>'+xhr.responseText+'</code>';
                    self.displayError('Failed to Retrieve Page', body);
                }
            };
            // set width/height
            if( self.getPageWidth() ){
                settings.data = {
                    width: self.getPageWidth()
                };
            }
            else if( self.getPageHeight() ){
                settings.data = {
                    height: self.getPageHeight()
                }; 
            }
            // retrieve page as text/html
            $.ajax(el.data('magazine').pages[i-1], settings);
            // retrieve page links for editor
            if(o.editable){
                self.getPageLinks();
            }
        },
        
        getPage: function(){
            return $(this.element).find('.magazine-page').first();
        },

        /**
         * Display an error dialog box
         */
        displayError: function(title, message){
            // create dialog
            var dialog = $('<div class="ui-dialog"></div>');
            dialog.append('<div class="ui-state-error">'+message+'</div>');
            dialog.dialog({
                autoOpen: false,
                title: 'Error: '+title,
                width: $(window).width() / 2,
                modal: true
            });
            // display dialog
            dialog.dialog('open');
        },
        
        /**
         * Display the navigation controls
         */
        displayNavigationControls: function(){
            var self = this, o = this.options, el = this.element;
            // add TABLE
            var nav = $('<table class="magazine-navigation-controls">' +
                '<tr><td>'+
                '<h2> </h2>' +
                '<span class="magazine-created"> </span>' +
                '</td><td>' +
                '<button type="submit" class="magazine-button magazine-zoom">' +
                '<img src="/client/buttons/zoom.png" alt="Zoom" /></button>' +
                '<button type="submit" class="magazine-button magazine-print">' +
                '<img src="/client/buttons/print.png" alt="Print" /></button>' +
                '<button type="submit" class="magazine-button magazine-email">' +
                '<img src="/client/buttons/email.png" alt="E-mail" /></button>' +
                //'<button type="submit" class="magazine-button magazine-embed">' +
                //'<img src="/client/buttons/embed.png" alt="Embed" /></button>' +
                //'<input type="text" class="magazine-search" placeholder="Search..." />' +
                '</td><td class="magazine-social">' +
                '<span class="magazine-follow">Follow us on: </span>' +
                '<button type="submit" class="magazine-button magazine-facebook">' +
                '<img src="/client/buttons/facebook.png" alt="Facebook" /></button>' +
                '<button type="submit" class="magazine-button magazine-twitter">' +
                '<img src="/client/buttons/twitter.png" alt="Twitter" /></button>' +
                '<button type="submit" class="magazine-button magazine-pinterest">' +
                '<img src="/client/buttons/pinterest.png" alt="Pinterest" /></button>' +
                '</td></tr></table>');
            el.before(nav);
            // add event handlers
            $('.magazine-zoom').live('click', function(){
                self.displayFullscreen();
            });
            $('.magazine-print').live('click', function(){
                self.displayPrint();
            });
            $('.magazine-email').live('click', function(){
                var url = 'mailto: ?';
                url += 'subject=Check Out "'+el.data('magazine').title+'"';
                url += '&body=Hey, check out this magazine I found: "'+el.data('magazine').title+'" <'+o.url+'>.';
                window.location = url;
            });
            /*
            $('.magazine-embed').live('click', function(){
                self.getEmbed();
            });
            */
            // get social settings; auto-attaches to buttons
            self.getSocialSettings();
            // add BUTTONs
            self.displayPreviousNext();
            // register 
            el.data('navigation-controls', nav);
        },
               
        /**
         * Add previous/next buttons to the container
         */
        displayPreviousNext: function(){
            var self = this, o = this.options, el = this.element;
            // add BUTTONs
            var previous = $('<button class="magazine-button magazine-previous">'+
                '<img src="/client/buttons/previous.png" alt="&larr; Previous"/></button>');
            var next = $('<button class="magazine-button magazine-next">'+
                '<img src="/client/buttons/next.png" alt="Next &rarr;" /></button>');
            el.prepend(next);
            el.prepend(previous);
            // set BUTTON height
            previous.height(el.height());
            next.height(el.height());
            // register BUTTON event handlers
            previous.click(function(event){
                self.previous();
                event.stopPropagation();
            });
            next.click(function(event){
                self.next();
                event.stopPropagation();
            });
        },
        
        /**
         * Remove previous next buttons; used when adding links
         */
        removePreviousNext: function(){
            var self = this, o = this.options, el = this.element;
            el.find('.magazine-previous, .magazine-next').remove();
        },
        
        /**
         * Display the container in full screen
         */
        displayFullscreen: function(){
            var self = this, o = this.options, el = this.element;
            var e = el.get(0);
            // get full screen
            if( e.requestFullscreen ){
                e.requestFullscreen();
            }
            else if(e.requestFullScreen){
                e.requestFullScreen();
            }
            else if(e.mozRequestFullScreen){
                e.mozRequestFullScreen();
            }
            else if(e.webkitRequestFullScreen){
                e.webkitRequestFullScreen();
            }
            else{
                self.displayError('Missing Feature', 'Your browser does not support the HTML5 Fullscreen API.');
                return false;
            }
            // save prior dimension
            if(self.getPageWidth() % 50 == 0 ){
                el.data('prior_page_dimension', 'width');
                el.data('prior_page_size', self.getPageWidth());
            }
            else if( self.getPageHeight() % 50 == 0 ){
                el.data('prior_page_dimension', 'height');
                el.data('prior_page_size', self.getPageHeight());
            }
            // resize image
            if( $(window).height() > $(window).width() ){
                var width = Math.floor($(window).width() / 50) * 50;
                self.setPageWidth(width);
            }
            else{
                var height = Math.floor($(window).height() / 50) * 50;
                self.setPageHeight(height);
            }
            self.displayPage(el.data('page'));
            // resize on exit
            $(document).bind('webkitfullscreenchange mozfullscreenchange fullscreenchange', function(event){
                self.exitFullscreen();
            });
        },
        
        exitFullscreen: function(){
            var self = this, o = this.options, el = this.element;
            var e = el.get(0);
            // exit fullscreen
            if( e.exitFullscreen ){
                e.requestFullscreen();
            }
            else if(e.exitFullScreen){
                e.requestFullScreen();
            }
            else if(e.mozExitFullScreen){
                e.mozRequestFullScreen();
            }
            else if(e.webkitExitFullScreen){
                e.webkitRequestFullScreen();
            }
            // resize image
            if( !document.mozFullScreenElement && !document.webkitFullScreenElement && !document.fullScreenElement){
                if( el.data('prior_page_dimension') == 'width' ){
                    self.setPageWidth(el.data('prior_page_size'));
                }
                else{
                    self.setPageHeight(el.data('prior_page_size'));
                }
                self.displayPage(el.data('page'));
            }
        },
        
        /** 
         * Display print window
         */
        displayPrint: function(){
            var self = this, o = this.options, el = this.element;
            var current_url = window.location.href;
            var url = current_url.replace("service.php/magazine", 'print.php');
            window.open(url, '_blank');

        },
        
        /**
         * Display editing controls
         */
        displayEditingControls: function(){
            var self = this, o = this.options, el = this.element;
            // add DIV
            var edit = $('<div class="magazine-editing-controls"></div>');
            el.after(edit);
            // add BUTTON
            var add = $('<button class="magazine-button magazine-add-link">+ ADD LINK</button>');
            edit.prepend(add);
            // register BUTTON event handlers
            add.click(function(event){
                self.addLink();
                event.stopPropagation();
            });
            // add links container
            var links = $('<div class="magazine-editing-links"></div>');
            edit.append(links);
            // register BUTTON event handlers
            $('.magazine-link-delete').live('click', function(event){
                event.preventDefault();
                self.deleteLink(this);
            });
            // register
            el.data('editing-controls', edit);
            // create dialog
            var dialog = $('<div class="ui-dialog"></div>');
            dialog.append('URL: <input type="text" id="magazine-add-link-input" title="Ensure the URL contains http:// if it points to a location outside this site." />');
            //dialog.append('<p>Recent Links:</p>');
            dialog.dialog({
                autoOpen: false,
                title: 'Add Link',
                modal: true,
                buttons: {
                    "Ok": function(){
                        el.data('editing-link').url = $('#magazine-add-link-input').val();
                        self.saveLink(el.data('editing-link'));
                        $(this).dialog("close");    
                    },
                    "Cancel": function(){
                        $(this).dialog("close");
                    }
                }
            });
            // register
            el.data('editing-link-dialog', dialog);
            el.data('editing-link',  null);
        },
                
        /**
         * UI process for adding a link to the container, then
         * POSTing it to the server
         */
        addLink: function(){
            var self = this, o = this.options, el = this.element;
            // create link object
            var link = {
                magazine_id: el.data('magazine.id'),
                page: el.data('page'),
                url: '',
                x: 0,
                y: 0,
                width: 0,
                height: 0,
                original_image_width: 0,
                original_image_height: 0
            }
            // style as editing
            el.addClass('magazine-editing');
            // remove previous/next buttons
            self.removePreviousNext();
            // event
            $(document).mousedown(function(event){
                if(!$(event.target).is('.magazine-container *')){
                    self.cancelAddLink();
                    return false;
                }
                // find page
                var page = el.find('.magazine-page:first');
                // add box
                var box = $('<div class="magazine-link"></div>');
                $(page).append(box);
                // set image width/height
                link.original_image_width = el.find('img:first').width();
                link.original_image_height = el.find('img:first').height();
                // position box
                box.css('position', 'absolute');
                link.x = Math.round(event.pageX - $(page).offset().left);
                link.y = Math.round(event.pageY - $(page).offset().top);
                box.css('left', link.x);
                box.css('top', link.y);
                el.mousemove(function(event){
                    link.width = Math.round(event.pageX - ($(page).offset().left + link.x));
                    link.height = Math.round(event.pageY - ($(page).offset().top + link.y));
                    box.css('width', link.width);
                    box.css('height', link.height);
                });
                el.mouseup(function(event){
                    el.data('editing-link', link);
                    el.data('editing-link-dialog').dialog('open');
                    el.off('mousedown mousemove mouseup');
                    el.removeClass('magazine-editing');
                    self.displayPreviousNext();
                });
            });
        },
        
        cancelAddLink: function(){
            var self = this, o = this.options, el = this.element;
            $(document).off('mousedown');
            el.off('mousedown mousemove mouseup');
            el.removeClass('magazine-editing');
            self.displayPreviousNext();
        },
        
        /**
         * POSTs link to the server, then reloads the magazine
         * page
         */
        saveLink: function(link){
            var self = this, o = this.options, el = this.element;
            // create url
            var url = o.url.replace(/service\.php\/magazine.+/, 'service.php/link');
            // POST
            $.post(url, link, function(data, status, xhr){
                self.displayPage(link.page);   
            }, 'json');
        },
        
        /**
         * DELETE link
         */
        deleteLink: function(link){
            var self = this, o = this.options, el = this.element;
            // make AJAX call to delete this link
            $.ajax($(link).attr('href'), {
                data: {
                    accept: 'application/json'
                },
                dataType: 'json',
                success: function(data, status, xhr){
                    var id = $(link).parent('li').attr('id'); // @TODO: storing same ID in two elements
                    // remove list element
                    $(link).parent('li').remove();
                    // remove magazine page link
                    $('#'+id).remove();
                    // callback
                    self._trigger('link_deleted');
                }, 
                error: function(xhr, status, error){
                    var body = '<pre>URL: '+o.url+'</pre>';
                    body += '<pre>Status: '+xhr.status+' '+xhr.statusText+'</pre>';
                    body += '<pre>Body: '+xhr.responseText+'</pre>';
                    self.displayError('Failed to Retrieve Page', body);
                }
            });
        },
 
        /**
         * Display the next page
         */
        next: function(){
            var self = this, o = this.options, el = this.element;
            if( el.data('page') < el.data('magazine').pages.length){
                el.data('page', el.data('page') + 1);
                self.displayPage(el.data('page'));
                self._trigger('page_next');
            }
        },
        
        /**
         * Display the previous page
         */
        previous: function(){
            var self = this, o = this.options, el = this.element;
            if( el.data('page') > 1 ){
                el.data('page', el.data('page') - 1);
                self.displayPage(el.data('page'));
                self._trigger('page_previous');
            }
        },
        
        activateSocialSettings: function(){
            var self = this, o = this.options, el = this.element;
            // verify
            var nav = el.data('navigation-controls');
            if( typeof el.data('social-settings') == "undefined"){
                nav.find('.magazine-social').remove();
            }
            // link each button
            var s = el.data('social-settings');
            for(var p in s){
                var url = s[p].toString();
                if(url){
                    // open new window
                    nav.find('.magazine-'+p).data('href', url);
                    nav.find('.magazine-'+p).click(function(){
                        window.open($(this).data('href'), '_blank');
                    });
                }
                else{
                    // or remove the button
                    nav.find('.magazine-'+p).remove();
                }
            }
        },
        
        getPageWidth: function(){
            return this.element.data('width');
        },
        
        setPageWidth: function(width){
            var self = this, o = this.options, el = this.element;
            // validate
            try{
                if( typeof width != 'number' ) throw "Width is not a number.";
                if( width % 50 != 0 ) throw "Width is not a multiple of 50 (the server caches images in 50 pixel increments).";
            }
            catch(e){
                self.displayError('Invalid Width', e);
            }
            // set in data
            el.data('height', null);
            el.data('width', width);
            // set in element
            el.width(width);
            // if necessary, get new image
            if( self.getPageWidth() != width ){
                self.displayPage(el.data('page'), width);
            }
        },
        
        getPageHeight: function(){
            return this.element.data('height');
        },
        
        setPageHeight: function(height){
            var self = this, o = this.options, el = this.element;
            // validate
            try{
                if( typeof height != 'number' ) throw "Height is not a number.";
                if( height % 50 != 0 ) throw "Height is not a multiple of 50 (the server caches images in 50 pixel increments).";
            }
            catch(e){
                self.displayError('Invalid Height', e);
            }
            // set in data
            el.data('width', null);
            el.data('height', height);
            // set in element
            el.height(height);
            // if necessary, get new image
            if( self.getPageHeight() != height ){
                self.displayPage(el.data('page'), null, height);
            }
        },
        
        /**
         * @TODO
         */
        resize: function(width, height){
            var self = this, o = this.options, el = this.element;
            // resize container
            el.height(height);
            el.width(width);
            // resize navigation
            if( o.showNavigation ){
                el.data('navigation-bar').width(width);
            }
            // resize edit controls
            if( o.editable ){
                el.data('edit-bar').width(width);
            }
            
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