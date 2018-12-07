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
		if (jQuery("textarea#query").length) {
			window.editor = CodeMirror.fromTextArea($("#query")[0], {
				mode: "text/x-pgsql",
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
				//foldGutter: {rangeFinder: CodeMirror.fold.brace	},
				gutters: ["CodeMirror-linenumbers", "CodeMirror-foldgutter"]
			});
		}
		if (window.inPopUp) {
			jQuery("table.tabs").prependTo("body");
			jQuery("table.printconnection").prependTo(".sqlform");
		}

		jQuery(".CodeMirror")
			.focus()
			.css("height", $("#query").attr("rows") * 26);

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
				jQuery(".CodeMirror").resizable();
			}, 1500);
		}
	});
}
