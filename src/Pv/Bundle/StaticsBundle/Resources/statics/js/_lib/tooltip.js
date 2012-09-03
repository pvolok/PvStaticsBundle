(function() {
  var $holderEl = $('<div class="tt"></div>').hide();
  $(document).ready(function() {
    $holderEl.appendTo(document.body);
  });

  function setPosition(baseElement, position) {
    var $baseElement = $(baseElement);
    var width = $holderEl.outerWidth();
    var height = $holderEl.outerHeight();
    var baseWidth = $baseElement.outerWidth();
    var baseHeight = $baseElement.outerHeight();
    var baseOffset = $baseElement.offset();
    var $window = $(window);
    var windowWidth = $window.width();
    var windowHeight = $window.height();

    var top, left;

    switch (position) {
      case G.Tooltip.TOP_CENTER: {
        top = baseOffset.top - $holderEl.outerHeight();
        left = baseOffset.left
            + ($baseElement.outerWidth() - $holderEl.outerWidth()) / 2;
        break;
      }
      case G.Tooltip.BOTTOM_CENTER: {
        top = baseOffset.top + baseHeight;
        left = baseOffset.left + (baseWidth - width) / 2;
        break;
      }
      case G.Tooltip.RIGHT_TOP: {
        if (baseOffset.top - $window.scrollTop() + height > windowHeight) {
          var tmpTop = baseOffset.top + baseHeight - height;
          if (tmpTop < $window.scrollTop()) {
            top = $window.scrollTop();
          } else {
            top = tmpTop
          }
        } else {
          top = baseOffset.top;
        }
        if (baseOffset.left - $window.scrollLeft() + baseWidth + width > windowWidth) {
          left = baseOffset.left - width;
        } else {
          left = baseOffset.left + baseWidth;
        }
        break;
      }
    }
    
    $holderEl.css('top', top + 'px').css('left', left + 'px');
  }

  G.tooltip = G.Tooltip = {
    TOP_CENTER: 0,
    RIGHT_TOP: 1,
    BOTTOM_CENTER:2,

    show: function(html, baseElement, position, decorated) {
      if (decorated) {
        html = G.tooltip.decorate(html);
      }

      $holderEl.html(html).show();

      setPosition(baseElement, position);
    },

    hide: function() {
      $holderEl.hide();
    },

    decorate: function(html) {
      return '<div class="tt-dec">' + html + '</div>';
    }
  };
})();