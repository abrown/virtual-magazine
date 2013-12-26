<!-- form -->
<h2>Edit Magazine</h2>
<form id="magazine-create" method="POST" action="<?php echo WebUrl::createAnchoredUrl("magazine/{$data->id}?method=PUT", false); ?>" enctype="multipart/form-data">
    <fieldset>
        <legend>URL</legend>
        <p>The URL to this magazine is: <a href="<?php echo WebUrl::createAnchoredUrl($data->getURI()); ?>" style="font-weight: bold;"><?php echo WebUrl::createAnchoredUrl($data->getURI()); ?></a>.</p>
    </fieldset>
    <fieldset>  
        <legend>Data</legend>
        <label for="title">Title</label>
        <input type="text" id="title" name="title" value="<?php echo $data->title; ?>" title="Enter the title to display for this magazine." required="required"/>
        <label for="description">Description</label>
        <input type="text" id="description" name="description" value="<?php echo $data->description; ?>" title="Enter a description to display for this magazine." />
        <label for="tracking_code" title="Enter the Google Analytics tracking code to associate with this magazine.">Google Analytics Tracking Code</label>
        <input type="text" id="tracking_code" name="tracking_code" value="<?php echo $data->tracking_code; ?>" title="Enter the Google Analytics tracking code to associate with this magazine." />
    </fieldset>
    <p>
        <input type="submit" id="magazine-submit" class="button" value="Save magazine"/>
    </p>
</form>

<!-- virtual-mag -->
<link rel="stylesheet" type="text/css" href="<?php echo WebUrl::create('client/jquery/jquery-ui-1.8.22.custom.css'); ?>" />
<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.min.js"></script>
<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js"></script>
<script type="text/javascript" src="<?php echo WebUrl::create('client/pageslide.js'); ?>"></script>
<script type="text/javascript">
    $(document).ready(function () {
        $("div#virtual-mag").pageslide({
            PAGE_WIDTH: 1100,        // dimensions of a page in the book
            PAGE_HEIGHT: 1555,       // dimensions of a page in the book
            BASE_URL: '<?php echo WebUrl::createAnchoredUrl("page/{$data->id}/[page#]?width=1100"); ?>', // URL to load pages from, the page number will replace "[page#]"; for this, pages are 1-indexed (i.e. start with 1, 2, 3...)
            use_ajax_loading: true, // uses the BASE_URL above to load pages into the book
            show_buttons: true,     // show next/previous buttons
            finished: function(){
                getLinksFor($('#virtual-mag').data('pageslide').page + 1, $('#links-wrapper'));
            }
        });
        getLinksFor(1, $('#links-wrapper'));
    });
    
    /**
     * Events
     */
    $(document).ready(function(){
        // add link
        $('#add-link').click(function(e){
            e.preventDefault();
            e.stopPropagation();
            // use dialog to get URL
            var url = '';
            var dialog = $('<div class="ui-dialog">URL: <input type="text" id="magazine-add-link-input" title="Ensure the URL contains http:// if it points to a location outside this site." /></div>').appendTo(document);
            dialog.dialog({
                title: 'Add Link URL',
                modal: true,
                buttons: {
                    "Ok": function(){
                        url = $('#magazine-add-link-input').val();
                        $(this).dialog("close");
                        addLinkFor($('#virtual-mag').data('pageslide').page, url);
                    },
                    "Cancel": function(){
                        cancel = true;
                        $(this).dialog("close");
                    }
                }
            });

        });
        // delete link
        $('.delete-link').live('click', function(e){
            e.preventDefault();
            e.stopPropagation();
            var link = this;
            $.ajax($(link).attr('href'), {
                'contentType': 'application/json',
                'accept': 'application/json',
                success: function(data, status, xhr){
                    var id = $(link).parent('li').attr('id'); // @TODO: storing same ID in two elements
                    $(link).parent('li').remove(); // remove list element
                    $('#'+id).remove(); // remove magazine page link
                }, 
                error: function(xhr, status, error){
                    alert('Failed to delete link.');
                }
            });
        });
    });
    
    /**
     * Retrieve links for this page
     */
    function getLinksFor(page, element){
        // create url
        var url = '<?php echo WebUrl::createAnchoredUrl("page/{$data->id}/[page#]?method=OPTIONS"); ?>';
        url = url.replace('[page#]', page);
        // make AJAX call
        $.get(url)
        .success(function(data, status, xhr){
            element.html(data);
            return data;
        })
        .error(function(xhr, status, error){
            alert('Failed to retrieve page links.');
        });
    }
    
    /**
     * Add a link to this page
     */
    var box;
    function addLinkFor(page, url){
        var el = $('#virtual-mag');
        var widget = el.data('pageslide');
        // create link object
        var link = {
            magazine_id: '<?php echo $data->id; ?>',
            page: page + 1,
            url: url,
            x: 0,
            y: 0,
            width: 0,
            height: 0,
            original_image_width: 0,
            original_image_height: 0
        }
        // setup edit
        el.addClass('editing');
        el.pageslide('disable');
        // add box
        var p = $(widget.pages.get(page)).css('position', 'relative');
        box = $('<div class="link"></div>').css('position', 'absolute');
        p.append(box);
        // event
        el.one('mousedown', function(event){
            event.preventDefault();
            // set image width/height
            link.original_image_width = widget.options.PAGE_WIDTH;
            link.original_image_height = widget.options.PAGE_HEIGHT;
            link.x = Math.round(event.pageX - $(p).offset().left);
            link.y = Math.round(event.pageY - $(p).offset().top);
            box.css('left', link.x).css('top', link.y);
        });
        el.on('mousemove', move);
        function move(event){
            event.preventDefault();
            link.width = Math.round(event.pageX - ($(p).offset().left + link.x));
            link.height = Math.round(event.pageY - ($(p).offset().top + link.y));
            box.css('width', link.width).css('height', link.height);
        };
        el.one('mouseup', function(event){
            el.off('mousemove', move)
            el.removeClass('editing');
            el.pageslide('enable');
            saveLink(link);
        });
    }
    
    /**
     * Save the given link by POSTing it to the server
     */
    function saveLink(link){
        // create url
        var url = '<?php echo WebUrl::createAnchoredUrl("link?method=POST"); ?>';
        //url = url.replace('[page#]', page);
        // POST
        $.post(url, link, function(data, status, xhr){
            getLinksFor($('#virtual-mag').data('pageslide').page + 1, $('#links-wrapper'));
            box.attr('id', 'link-'+data);
        });
    }
</script>

<!-- add links -->
<fieldset>
    <legend>Add links</legend>
    <button id="add-link">+ ADD LINK</button>
    <div id="links-wrapper"></div>
</fieldset>

<p></p>

<!-- virtual-mag -->
<div id="virtual-mag"></div>