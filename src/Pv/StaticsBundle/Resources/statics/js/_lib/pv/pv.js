(function() {
    pv = {};

    var MATCHER_FOR_ESCAPE_HTML = /[\x00\x22\x26\x27\x3c\x3e]/g;

    var ESCAPE_MAP_FOR_ESCAPE_HTML = {
        '\x00': '\x26#0;',
        '\x22': '\x26quot;',
        '\x26': '\x26amp;',
        '\x27': '\x26#39;',
        '\x3c': '\x26lt;',
        '\x3e': '\x26gt;'
    };

    var REPLACER_FOR_ESCAPE_HTML = function(ch) {
        return ESCAPE_MAP_FOR_ESCAPE_HTML[ch];
    };


    pv.keys = function(map) {
        var mapKeys = [];
        for (var key in map) {
            mapKeys.push(key);
        }
        return mapKeys;
    };

    pv.escapeHtml = function(value) {
        return String(value).replace(MATCHER_FOR_ESCAPE_HTML,
            REPLACER_FOR_ESCAPE_HTML);
    }
})();
