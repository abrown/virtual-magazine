<?php

/**
 * Convenience class for accessing all magazines 
 */
class Library extends ResourceList {

    protected $storage = array('type' => 'json', 'location' => 'server/db/library.json');
    protected $item_type = 'Magazine';
    protected $representation = 'text/html';

    /**
     * Add templating to 'text/html' requests
     * @param Representation $representation
     * @return Representation 
     */
    function OUTPUT_TRIGGER($representation) {
        if ($representation->getContentType() == 'text/html') {
            $representation->setTemplate('site/templates/admin.php', WebTemplate::PHP_FILE);
            $representation->getTemplate()->replace('title', $this->getURI());
            $representation->getTemplate()->replaceFromPHPFile('content', 'server/ui/library.php' , array('data' => $representation->getData()));
        }
        return $representation;
    }

}
