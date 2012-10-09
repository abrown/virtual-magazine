<?php

/**
 * A link is represented as a DIV overlayed on a page image. 
 */
class Link extends ResourceItem {

    protected $storage = array('type' => 'json', 'location' => 'server/links.json');

    /**
     * Internal ID for the link
     * @var int 
     */
    public $id;

    /**
     * Magazine page
     * @var int 
     */
    public $page;

    /**
     * Magazine ID
     * @var string 
     */
    public $magazine_id;

    /**
     * URL this link points to; 'page-view.php' will register a JS onclick event
     * so the link box will redirect the browser to this string; note that if
     * this does not start with 'http://', the browser will look for this string
     * on the current site.
     * @var string 
     */
    public $url;

    /**
     * Location of top left corner of the link on the image, as originally saved;
     * this will be altered in 'page-view.php' to match the current image dimensions
     * @var int 
     */
    public $x;

    /**
     * Location of top left corner of the link on the image, as originally saved;
     * this will be altered in 'page-view.php' to match the current image dimensions
     * @var int 
     */
    public $y;

    /**
     * Size of the link box on the image, as originally saved;
     * this will be altered in 'page-view.php' to match the current image dimensions
     * @var int 
     */
    public $width;

    /**
     * Size of the link box on the image, as originally saved;
     * this will be altered in 'page-view.php' to match the current image dimensions
     * @var int 
     */
    public $height;

    /**
     * Original size of the image; this is used by 'page-view.php' to maintain
     * the correct size and location of the link boxes
     * @var int 
     */
    public $original_image_width;

    /**
     * Original size of the image; this is used by 'page-view.php' to maintain
     * the correct size and location of the link boxes
     * @var int 
     */
    public $original_image_height;

    /**
     * On change to a link, notify the cache of a change to the page
     * @param Representation $representation
     * @return Representation 
     */
    public function POST_OUTPUT_TRIGGER($representation) {
        // update cache
        $uri = "page/{$this->magazine_id}/{$this->page}";
        Cache::getInstance()->DELETE($uri);
        // return
        return $representation;
    }

    /**
     * On change to a link, notify the cache of a change to the page
     * @param Representation $representation
     * @return Representation 
     */
    public function PUT_OUTPUT_TRIGGER($representation) {
        // update cache
        $uri = "page/{$this->magazine_id}/{$this->page}";
        Cache::getInstance()->DELETE($uri);
        // return
        return $representation;
    }

    /**
     * On change to a link, notify the cache of a change to the page
     * @param Representation $representation
     * @return Representation 
     */
    public function DELETE_OUTPUT_TRIGGER($representation) {
        // update cache
        $uri = "page/{$this->magazine_id}/{$this->page}";
        Cache::getInstance()->DELETE($uri);
        return $representation;
    }

}