if (typeof soy == 'undefined') {
    soy = {};
}

soy.$$getMapKeys = pv.keys;
soy.$$escapeHtml = pv.escapeHtml;

soy.$$augmentMap = function(baseMap, additionalMap) {

    // Create a new map whose '__proto__' field is set to baseMap.
    /** @constructor */
    function TempCtor() {}
    TempCtor.prototype = baseMap;
    var augmentedMap = new TempCtor();

    // Add the additional mappings to the new map.
    for (var key in additionalMap) {
        augmentedMap[key] = additionalMap[key];
    }

    return augmentedMap;
};
