<?php

/**
 * A Page is a dynamically-created object; it has no storage. It resizes and 
 * displays raw HTML using the 'page-view.php' and 'page-links.php' templates.
 * It only declares to HTTP methods: GET, for retrieving the page, and OPTIONS,
 * for retrieving an ordered HTML list of the links on the page.
 */
class Page extends ResourceGeneric {

    /**
     * Page number
     * @var int 
     */
    public $page;

    /**
     * Magazine ID
     * @var string 
     */
    public $magazine_id;

    /**
     * List of links on this page
     * @var array 
     */
    public $links = array(); // list of links on this page

    /**
     * URL to the JPEG for this page
     * @var string 
     */
    public $image;

    /**
     * Width of the page; correct aspect ratios are maintained using the methods
     * getCorrespondingHeight() and getCorrespondingWidth()
     * @var type 
     */
    public $width;

    /**
     * Height of the page; correct aspect ratios are maintained using the methods
     * getCorrespondingHeight() and getCorrespondingWidth()
     * @var type 
     */
    public $height;

    /**
     * If no width is set, this is the default width to display 
     */

    const DEFAULT_WIDTH = 800;

    /**
     * Save JPEGs with this quality; ranges from 0 to 100 
     */
    const JPEG_QUALITY = 95;

    /**
     * Constructor; set page number and magazine ID using either the constructor
     * parameters or URL tokens in the form: .../[resource]/[magazine_id]/[page]
     * @throws Error
     */
    public function __construct($magazine_id = null, $page = null) {
        // get URL tokens; pattern: /[resource]/[magazine_id]/[page]/...
        try {
            $tokens = WebUrl::getTokens();
        } catch (Error $e) {
            
        }
        // get magazine ID
        if ($magazine_id !== null) {
            $this->magazine_id = $magazine_id;
        } else {
            $this->magazine_id = @$tokens[1];
        }
        // get page
        if ($page !== null) {
            $this->page = $page;
        } else {
            $this->page = @$tokens[2];
        }
        // validate
        if (!$this->magazine_id) {
            throw new Error('No magazine ID set in url: .../[resource]/[magazine_id]/[page]/', 400);
        }
        if (!$this->page) {
            throw new Error('No page set in url: .../[resource]/[magazine_id]/[page]/', 400);
        }
    }

    /**
     * Get resource URI
     * @return string
     */
    public function getURI() {
        return 'page/' . $this->magazine_id . '/' . $this->page;
    }

    /**
     * Use 'page-view.php' as the template for this resource
     * @param type $representation
     * @return type
     */
    public function GET_OUTPUT_TRIGGER($representation) {
        if ($representation->getContentType() == 'text/html') {
            $representation->setTemplate(get_vm_dir(). DS . 'server' . DS . 'ui' . DS . 'page-view.php', WebTemplate::PHP_FILE);
            $data = $representation->getData(); // can't pass getData() as a reference in strict PHP
            $representation->getTemplate()->setVariable('data', $data);
        }
        return $representation;
    }

    /**
     * Use 'page-view.php' as the template for this resource
     * @param type $representation
     * @return type
     */
    public function OPTIONS_OUTPUT_TRIGGER($representation) {
        if ($representation->getContentType() == 'text/html') {
            $representation->setTemplate(get_vm_dir(). DS . 'server' . DS . 'ui' . DS . 'page-links.php', WebTemplate::PHP_FILE);
            $data = $representation->getData(); // can't pass getData() as a reference in strict PHP
            $representation->getTemplate()->setVariable('data', $data);
        }
        return $representation;
    }

    /**
     * GETs a page from a magazine; removed code limiting pages to 50px increments.
     * @return Page
     */
    public function GET() {
        // get resolution
        $this->width = (int) WebHttp::getParameter('width');
        $this->height = (int) WebHttp::getParameter('height');
        // case: neither specified
        if (!$this->width && !$this->height) {
            $this->width = self::DEFAULT_WIDTH;
        }
        // case: no height specified
        if (!$this->height) {
            $this->height = self::getCorrespondingHeight($this->magazine_id, $this->page, $this->width);
        }
        // case: no width specified
        if (!$this->width) {
            $this->width = self::getCorrespondingHeight($this->magazine_id, $this->page, $this->height);
        }
        // resize image; if possible, use GD library to resize the image
        if (function_exists('gd_info')) {
            self::resizeJPEG($this->magazine_id, $this->page, $this->width, $this->height);
            $this->image = self::getUrltoImage($this->magazine_id, $this->page, $this->width, $this->height);
        } else {
            $this->image = self::getUrltoImage($this->magazine_id, $this->page);
        }
        // add page links
        $this->links = $this->OPTIONS();
        // return
        return $this;
    }

    /**
     * Return a list of links for this page
     * @return array of Links
     */
    public function OPTIONS() {
        // add page links
        $_link = new Link();
        foreach ($_link->getStorage()->search('magazine_id', $this->magazine_id) as $key => $value) {
            if ($value->page == $this->page) {
                $this->links[] = $value;
            }
        }
        // return
        return $this->links;
    }

    /**
     * Resize a JPEG according to the specified width and height
     * @TODO test
     * @param string $id
     * @param int $page
     * @param int $width
     * @param int $height
     * @return string location of the image
     * @throws Error
     */
    public static function resizeJPEG($id, $page, $width = null, $height = null) {
        // test for GD
        if (!function_exists('gd_info')) {
            throw new Error('GD library must be enabled for image resizing to work.', 500);
        }
        // test for base image
        $path = self::getPathToImage($id, $page);
        if (!file_exists($path)) {
            throw new Error('Missing file: ' . $path, 404);
        }
        if (!$width && !$height) {
            return $path;
        }
        // test if already created; getPathToImage() will handle logic regarding width/height/base dimensions
        $resized_path = self::getPathToImage($id, $page, $width, $height);
        if (file_exists($resized_path)) {
            return $resized_path;
        }
        // otherwise, create it; find width/height
        if ($width && !$height) {
            $height = self::getCorrespondingHeight($id, $page, $width);
        } elseif ($height && !$width) {
            $width = self::getCorrespondingWidth($id, $page, $height);
        }
        // resize
        list($base_width, $base_height, ) = getimagesize($path);
        $source = imagecreatefromjpeg($path);
        $destination = imagecreatetruecolor($width, $height);
        imagecopyresampled($destination, $source, 0, 0, 0, 0, $width, $height, $base_width, $base_height);
        // save
        imagejpeg($destination, $resized_path, self::JPEG_QUALITY);
        // return
        return $resized_path;
    }

    /**
     * Return URL to page/image
     * @param string $id
     * @param int $page
     * @param int $width
     * @param int $height
     * @return string
     */
    public static function getUrltoImage($id, $page, $width = null, $height = null) {
        $url = WebUrl::create('server/data/' . $id);
        // add page
        $url .= '(' . $page . ')';
        // add width/height
        if ($width) {
            $url .= $width . 'w';
        } elseif ($height) {
            $url .= $height . 'h';
        }
        // finish and return
        $url .= '.jpg';
        return $url;
    }

    /**
     * Return path to page/image.
     * @param string $id
     * @param int $page
     * @param int $width
     * @param int $height
     * @return string
     */
    public static function getPathToImage($id, $page, $width = null, $height = null) {
        $path = Magazine::getPathToData() . DS . $id;
        // add page
        $path .= '(' . $page . ')';
        // add width/height
        if ($width) {
            $path .= $width . 'w';
        } elseif ($height) {
            $path .= $height . 'h';
        }
        // finish
        $path .= '.jpg';
        return $path;
    }

    /**
     * Return the corresponding height for a given width so that
     * the height-width ratio is preserved.
     * @param string $id
     * @param int $page
     * @param int $width
     * @return int
     */
    public static function getCorrespondingHeight($id, $page, $width) {
        $path = self::getPathToImage($id, $page);
        if (!file_exists($path)) {
            throw new Error("The following file was not found: {$path}. Ensure that the page exists in the original PDF and that it has been converted to JPG.", 404);
        }
        // calculate ratio
        list($current_width, $current_height, ) = getimagesize($path);
        $ratio = $current_width / $current_height;
        $height = (int) ($width / $ratio);
        // return
        return $height;
    }

    /**
     * Return the corresponding width for a given height so that
     * the height-width ratio is preserved.
     * @param string $id
     * @param int $page
     * @param int $height
     * @return int
     */
    public static function getCorrespondingWidth($id, $page, $height) {
        $path = self::getPathToImage($id, $page);
        if (!file_exists($path)) {
            throw new Error("The following file was not found: {$path}. Ensure that the page exists in the original PDF and that it has been converted to JPG.", 404);
        }
        // calculate ratio
        list($current_width, $current_height, ) = getimagesize($path);
        $ratio = $current_width / $current_height;
        $width = (int) ($height * $ratio);
        // return
        return $width;
    }

}