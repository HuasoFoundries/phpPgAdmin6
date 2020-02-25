var avoid_bogus_character = true;
var windowsize;
function autoResize(cb) {
  //console.log('window size', windowsize);
  jQuery(".CodeMirror").css(
    "height",
    jQuery(window).innerHeight() - windowsize.height_difference
  );
  if (cb) {
    requestAnimationFrame(cb);
  }
}
function setResizable() {
  requestAnimationFrame(() => {
    jQuery(".CodeMirror")
      .css("resize", "both")
      .attr("resizable", "true")
      .resizable({
        handles: "all",
        resize: function() {
          window.codemirrorEditor.setSize($(this).width(), $(this).height());
        }
      });
  });
}

window.addEventListener("load", event => {
  console.log("'Todos los recursos terminaron de cargar!");
  if (
    !avoid_bogus_character ||
    typeof CodeMirror === "undefined" ||
    !document.querySelector("textarea#query")
  ) {
    return;
  }
  jQuery(document).ready(function() {
    windowsize = {
      height: jQuery(window).innerHeight(),
      width: jQuery(window).innerWidth()
    };

    let modeCM,
      specCM,
      editorMode = "text/x-pgsql";

    CodeMirror.autoLoadMode = function(instance, mode) {
      console.log({ instance, mode });
      if (CodeMirror.modes.hasOwnProperty(mode)) {
        return;
      }
      CodeMirror.requireMode(mode, function() {
        instance.setOption("mode", instance.getOption("mode"));
        jQuery(".CodeMirror").css("height", $("#query").attr("rows") * 26);

        requestAnimationFrame(() => {
          if (window.inPopUp) {
            windowsize.height_difference =
              windowsize.height - jQuery(".CodeMirror").innerHeight();
            jQuery(window).on("resize", autoResize);
          }
          window.codemirrorEditor = instance;
          autoResize(setResizable);
        });
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

    let codemirrorEditor = CodeMirror.fromTextArea(
      document.querySelector("#query"),
      /*{
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
          gutters: ["CodeMirror-linenumbers", "CodeMirror-lint-markers"]
        }*/ {
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
        }
        //foldGutter: true,
        // foldGutter: { rangeFinder: CodeMirror.fold.brace },
        //gutters: ["CodeMirror-linenumbers", "CodeMirror-foldgutter"]
      }
    );

    var modeInfo = CodeMirror.findModeByMIME(editorMode);
    console.log({ editorMode, modeInfo });
    if (modeInfo && modeInfo.mode) {
      codemirrorEditor.setOption("mode", editorMode);
      CodeMirror.autoLoadMode(codemirrorEditor, modeInfo.mode);
    }
  });
});
