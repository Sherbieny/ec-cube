const $ = require("jquery");
global.$ = global.jQuery = $;

require("slick-carousel");
require("slick-carousel/slick/slick.css");
require("slick-carousel/slick/slick-theme.css");

require("bootstrap");

//Custom Shared JS
require("shared/initializer.js");

//Custom Default JS
require("./initializer.js");
