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
  let modeCM,
    specCM,
    editorMode = "text/x-pgsql";

  CodeMirror.autoLoadMode = function(instance, mode) {
    console.log({ instance, mode });
    if (!CodeMirror.modes.hasOwnProperty(mode))
      CodeMirror.requireMode(mode, function() {
        instance.setOption("mode", instance.getOption("mode"));
        jQuery(".CodeMirror").css("height", $("#query").attr("rows") * 26);

        window.setTimeout(function() {
          jQuery(".CodeMirror")
            .attr("resizable", "true")
            .focus();
        }, 2500);
      });
  };
  CodeMirror.commands.sendQuery = function() {
    jQuery("#sqlform").submit();
  };
  /*
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
*/

  if (document.querySelector("textarea#query")) {
    window.editor = CodeMirror.fromTextArea(document.querySelector("#query"), {
      //  mode: "text/x-pgsql",
      indentWithTabs: true,
      smartIndent: true,
      lineNumbers: true,
      electricChars: true,
      matchBrackets: true,
      autofocus: true,
      addModeClass: true,
      extraKeys: {
        "Ctrl-Space": "autocomplete",
        "Ctrl-Enter": "sendQuery"
      },
      //foldGutter: {rangeFinder: CodeMirror.fold.brace },
      gutters: ["CodeMirror-linenumbers", "CodeMirror-foldgutter"]
    });

    var modeInfo = CodeMirror.findModeByMIME(editorMode);
    console.log({ editorMode, modeInfo });
    if (modeInfo) {
      mode = modeInfo.mode;
      spec = modeInfo;

      window.editor.setOption("mode", modeInfo);
      CodeMirror.autoLoadMode(window.editor, modeInfo.mode);
    }
  }

  if (window.inPopUp) {
    jQuery(window).on("resize", autoResize);
  }

  window.setTimeout(function() {
    autoResize(() => {
      jQuery(".CodeMirror").css("resize", "both");
    });
  }, 1500);
});
