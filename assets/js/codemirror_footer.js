var avoid_bogus_character = true;
function autoResize(cb) {
  var windowsize = {
    height: jQuery(window).innerHeight(),
    width: jQuery(window).innerWidth()
  };
  windowsize.height_difference =
    windowsize.height - jQuery(".CodeMirror").innerHeight();

  //console.log('window size', windowsize);
  jQuery(".CodeMirror").css(
    "height",
    windowsize.height - windowsize.height_difference
  );
  if (cb) {
    requestAnimationFrame(cb);
  }
}
if (window.inPopUp) {
  jQuery("table.tabs").prependTo("body");
  jQuery("table.printconnection").prependTo(".sqlform");
}

jQuery(document).ready(function() {
  if (
    !avoid_bogus_character ||
    typeof CodeMirror === "undefined" ||
    !document.querySelector("textarea#query")
  ) {
    return;
  }

  CodeMirror.commands.sendQuery = function() {
    jQuery("#sqlform").submit();
  };

  window.editor = CodeMirror.fromTextArea(document.querySelector("#query"), {
    mode: "text/x-pgsql",
    indentWithTabs: true,
    smartIndent: true,
    lineNumbers: true,
    //electricChars: true,
    matchBrackets: true,
    autofocus: true,
    addModeClass: true,
    extraKeys: {
      "Ctrl-Enter": "sendQuery",
      "Ctrl-Q": function(cm) {
        cm.foldCode(cm.getCursor());
      }
    },
    foldGutter: true,
    //foldGutter: {rangeFinder: CodeMirror.fold.brace },
    gutters: ["CodeMirror-linenumbers", "CodeMirror-foldgutter"]
  });

  if (window.inPopUp) {
    jQuery(window).on("resize", autoResize);
  }

  window.setTimeout(function() {
    autoResize(() => {
      jQuery(".CodeMirror").css("resize", "both");
    });
  }, 1500);
});
