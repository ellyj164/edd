/**
 * Minimal jQuery Shim for FezaMarket
 * Provides basic jQuery functionality when CDN is not available
 */

(function(window) {
    'use strict';
    
    function $(selector) {
        if (typeof selector === 'string') {
            return new jQuery(document.querySelectorAll(selector));
        } else if (selector === document) {
            return new jQuery([document]);
        } else if (typeof selector === 'function') {
            // $(document).ready equivalent
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', selector);
            } else {
                selector();
            }
            return;
        }
        return new jQuery([selector]);
    }
    
    function jQuery(elements) {
        this.elements = Array.from(elements || []);
        this.length = this.elements.length;
        return this;
    }
    
    jQuery.prototype = {
        // Basic DOM manipulation
        on: function(event, handler) {
            this.elements.forEach(el => el.addEventListener(event, handler));
            return this;
        },
        
        off: function(event, handler) {
            this.elements.forEach(el => el.removeEventListener(event, handler));
            return this;
        },
        
        click: function(handler) {
            if (handler) {
                return this.on('click', handler);
            }
            this.elements.forEach(el => el.click());
            return this;
        },
        
        html: function(content) {
            if (content === undefined) {
                return this.elements[0] ? this.elements[0].innerHTML : '';
            }
            this.elements.forEach(el => el.innerHTML = content);
            return this;
        },
        
        text: function(content) {
            if (content === undefined) {
                return this.elements[0] ? this.elements[0].textContent : '';
            }
            this.elements.forEach(el => el.textContent = content);
            return this;
        },
        
        val: function(value) {
            if (value === undefined) {
                return this.elements[0] ? this.elements[0].value : '';
            }
            this.elements.forEach(el => el.value = value);
            return this;
        },
        
        addClass: function(className) {
            this.elements.forEach(el => el.classList.add(className));
            return this;
        },
        
        removeClass: function(className) {
            this.elements.forEach(el => el.classList.remove(className));
            return this;
        },
        
        hide: function() {
            this.elements.forEach(el => el.style.display = 'none');
            return this;
        },
        
        show: function() {
            this.elements.forEach(el => el.style.display = '');
            return this;
        },
        
        ready: function(handler) {
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', handler);
            } else {
                handler();
            }
            return this;
        },
        
        fadeOut: function(duration) {
            this.elements.forEach(el => {
                el.style.transition = 'opacity ' + (duration || 300) + 'ms';
                el.style.opacity = '0';
                setTimeout(() => el.style.display = 'none', duration || 300);
            });
            return this;
        },
        
        each: function(callback) {
            this.elements.forEach((el, index) => callback.call(el, index, el));
            return this;
        },
        
        attr: function(name, value) {
            if (value === undefined) {
                return this.elements[0] ? this.elements[0].getAttribute(name) : null;
            }
            this.elements.forEach(el => el.setAttribute(name, value));
            return this;
        },
        
        // AJAX functionality
        get: function(url, success) {
            return this.ajax({
                url: url,
                method: 'GET',
                success: success
            });
        },
        
        post: function(url, data, success) {
            return this.ajax({
                url: url,
                method: 'POST',
                data: data,
                success: success
            });
        },
        
        ajax: function(options) {
            const xhr = new XMLHttpRequest();
            xhr.open(options.method || 'GET', options.url);
            
            if (options.method === 'POST') {
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            }
            
            xhr.onload = function() {
                if (xhr.status === 200 && options.success) {
                    let response = xhr.responseText;
                    try {
                        response = JSON.parse(response);
                    } catch(e) {}
                    options.success(response);
                }
            };
            
            xhr.send(options.data || null);
            return xhr;
        }
    };
    
    // Static methods
    $.ajax = function(options) {
        return new jQuery([]).ajax(options);
    };
    
    $.get = function(url, success) {
        return $.ajax({url: url, method: 'GET', success: success});
    };
    
    $.post = function(url, data, success) {
        return $.ajax({url: url, method: 'POST', data: data, success: success});
    };
    
    $.ajaxSetup = function(options) {
        // Simple implementation - just store the beforeSend for now
        if (options.beforeSend) {
            $.ajaxSettings = $.ajaxSettings || {};
            $.ajaxSettings.beforeSend = options.beforeSend;
        }
    };
    
    // Make $ available globally
    window.$ = window.jQuery = $;
    
})(window);