G = {
  staticUrl: function($path) {
    return '/s/' + '/' + $path;
  },
  jsUrl: function(path) {
    if (!window.DEV) {
      path = path.replace(/\.js$/, '.' + window.lang + '.js');
    }
    return '/s/' + window.STATIC_VER + '/' + path;
  }
};