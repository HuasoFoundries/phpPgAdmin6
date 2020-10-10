module.exports = {
  "globDirectory": "assets/",
  "globPatterns": [
    "**/*.{css,eot,svg,png,js,xml,ico,jpg,gif,swf,html,css}"
  ],
  "swDest": "assets/sw.js",
  "swSrc": "assets/sw.dev.js",


  globIgnores: [

    "cbpapi/**",
    "cbpfactor/**",
    "css/**",
    "js/**",
    "*.json",

    "../workbox-config.js",
  ]
};
