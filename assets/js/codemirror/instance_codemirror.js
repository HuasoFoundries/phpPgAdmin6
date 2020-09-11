if (typeof CodeMirror !== 'undefined') {
  let modeCM,
    specCM,
    editorMode = 'text/x-pgsql';
  CodeMirror.autoLoadMode = function (instance, mode) {
    console.log({ instance, mode });
    if (!CodeMirror.modes.hasOwnProperty(mode)) {
      CodeMirror.requireMode(mode, function () {
        instance.setOption('mode', instance.getOption('mode'));
        jQuery('.CodeMirror').css('height', $('#query').attr('rows') * 26);

        window.setTimeout(function () {
          jQuery('.CodeMirror').attr('resizable', 'true').focus();
        }, 2500);
      });
    }
  };
  CodeMirror.commands.sendQuery = function () {
    jQuery('#sqlform').submit();
  };

  if (document.querySelector('textarea#query')) {
    window.editor = CodeMirror.fromTextArea(document.querySelector('#query'), {
      //  mode: "text/x-pgsql",
      indentWithTabs: true,
      smartIndent: true,
      lineNumbers: true,
      electricChars: true,
      matchBrackets: true,
      autofocus: true,
      addModeClass: true,
      extraKeys: {
        'Ctrl-Space': 'autocomplete',
        'Ctrl-Enter': 'sendQuery',
      },
      //foldGutter: {rangeFinder: CodeMirror.fold.brace },
      gutters: ['CodeMirror-linenumbers', 'CodeMirror-foldgutter'],
    });

    var modeInfo = CodeMirror.findModeByMIME(editorMode);
    console.log({ editorMode, modeInfo });
    if (modeInfo) {
      mode = modeInfo.mode;
      spec = modeInfo;

      window.editor.setOption('mode', modeInfo);
      CodeMirror.autoLoadMode(window.editor, modeInfo.mode);
    }
  }
}
