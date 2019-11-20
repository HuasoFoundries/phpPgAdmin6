var avoid_bogus_character = true;
if (avoid_bogus_character) {
  jQuery(document).ready(function() {
    if (typeof CodeMirror === "undefined") {
      return;
    }

    jQuery("#query").focus();
    CodeMirror.commands.sendQuery = function() {
      jQuery("#sqlform").submit();
    };

    /*var AUTOCOMPLETE_TABLES = {
      users: ["name", "score", "birthDate"],
      countries: ["name", "population", "size"]
    };
    CodeMirror.commands.autocomplete = function(cm) {
      CodeMirror.showHint(cm, CodeMirror.hint.sql, {
        tables: AUTOCOMPLETE_TABLES
      });
    };*/
    let mode,
      spec,
      editorMode = "text/x-pgsql";
    console.log(CodeMirror.modes);
    CodeMirror.autoLoadMode = function(instance, mode) {
      console.log({ instance, mode });
      if (!CodeMirror.modes.hasOwnProperty(mode))
        CodeMirror.requireMode(mode, function() {
          instance.setOption("mode", instance.getOption("mode"));
          jQuery(".CodeMirror").css("height", $("#query").attr("rows") * 26);

          if (location.pathname.indexOf("sqledit") !== -1) {
            var windowsize = {
              height: jQuery(window).innerHeight(),
              width: jQuery(window).innerWidth(),
              height_difference: height_difference
            };

            var height_difference =
              windowsize.height - jQuery(".CodeMirror").innerHeight();
            jQuery(window).on("resize", function() {
              var windowsize = {
                height: jQuery(window).innerHeight(),
                width: jQuery(window).innerWidth(),
                height_difference: height_difference
              };
              //console.log('window size', windowsize);
              jQuery(".CodeMirror").css(
                "height",
                windowsize.height - height_difference
              );
            });
          } else {
            window.setTimeout(function() {
              jQuery(".CodeMirror")
                .focus()
                .resizable();
            }, 2500);
          }
        });
    };

    if (document.querySelector("textarea#query")) {
      window.editor = CodeMirror.fromTextArea(
        document.querySelector("#query"),
        {
          //mode: "text/x-pgsql",
          /* indentWithTabs: true,
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
        gutters: ["CodeMirror-linenumbers", "CodeMirror-foldgutter"]*/
          indentWithTabs: true,
          // smartIndent: true,
          lineNumbers: true,
          // matchBrackets: true,
          //autofocus: true,
          extraKeys: {
            // "Ctrl-Space": "autocomplete",
            "Ctrl-Enter": "sendQuery"
          },
          //foldGutter: {rangeFinder: CodeMirror.fold.brace },
          gutters: ["CodeMirror-linenumbers", "CodeMirror-foldgutter"]
        }
      );

      var modeInfo = CodeMirror.findModeByMIME(editorMode);
      console.log({ editorMode, modeInfo });
      if (modeInfo) {
        mode = modeInfo.mode;
        spec = modeInfo;

        window.editor.setOption("mode", spec);
        CodeMirror.autoLoadMode(window.editor, mode);
      }
    }
    if (window.inPopUp) {
      jQuery("table.tabs").prependTo("body");
      jQuery("table.printconnection").prependTo(".sqlform");
    }
  });
}
