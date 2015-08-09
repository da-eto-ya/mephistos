var calculateDestination = function (y) {
    return y + 10;
    // only for testing
};

(function () {
    // dead code - so, must be removed in minified file
    var library = function () {
        var s = document.body;
    };
})();

(function () {
    // real code - so, should be included in minified version
    console.log("hi");
})();

var getLatency = function () {
    return 22;
};

(function($){
    $(function(){

        $('.button-collapse').sideNav();

    });
})(jQuery);
