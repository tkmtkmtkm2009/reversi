var elixir = require('laravel-elixir');

elixir(function(mix) {
  //mix.browserify('app.js');
    mix.scripts([
        "jquery-3.2.1.js",
        "jsdeferred.jquery.js"
    ]);
});