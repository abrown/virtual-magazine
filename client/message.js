
/**
 * Access by function
 */
function message(text, type, style){
    return new Message(text, type, style);
}

/**
 * Message constructor
 */
function Message(text, type, style){
    // create element
    this.element = document.createElement('div');
    this.element.className = 'message';
    this.element.innerHTML = text;
    // add styles
    for(var property in this.styles){
        this.element.style[property] = this.styles[property];
    }
    if(style instanceof Array){
        for(var property in style){
            this.element.style[property] = style[property];
        }
    }
    // apply color
    if(type && type in this.colors){
        this.element.style.backgroundColor = this.colors[type];
    }
    else{
        this.element.style.backgroundColor = this.colors['default'];
    }
    // add close button
    var close = document.createElement('span');
    close.style.cursor = 'pointer';
    close.style.cssFloat = 'right'; // all browsers
    close.style.styleFloat = 'right'; // IE hack
    close.style.marginRight = '1em';
    close.style.fontWeight = 'bold';
    close.style.fontSize = '1.5em';
    close.style.lineHeight = '1em';
    close.innerHTML = '&#215;';
    var self = this;
    close.addEventListener('click', function(){
        self.hide();
    });
    this.element.insertBefore(close, this.element.firstChild);
    // append element
    document.body.appendChild(this.element);
    // open
    this.show();
}

/**
 * Message class
 */
Message.prototype = {
    
    /**
     * Point to the message element
     */
    element: null,
    
    /**
     * Background colors
     */
    colors: {
        'default': '#272727',
        'warning': '#9F6000',
        'error': '#D8000C',
        'success': '#4F8A10'
    },
    
    /**
     * CSS styles
     */
    styles: {
        'display': 'none',
        'position': 'fixed',
        'top': '0',
        'left': '0',
        'width': '100%',
        'textAlign': 'center',
        'padding': '0.5em',
        'font': 'inherit',
        'color': '#fff'
    },
      
    /**
     * Show the message box
     */
    show: function(){
        this.element.style.display = 'block';
    },
    
    /**
     * Show the message box
     */
    hide: function(){
        this.element.style.display = 'none';
    }
}