$(document).ready(function() {
  if (window.inPopUp) {
    jQuery("table.tabs").prependTo("body");
    jQuery("table.printconnection").prependTo(".sqlform");
  }

  var the_action = $("#the_action").val();

  function get_action_params(from) {
    var action_params = [];
    if (the_action === "sql") {
      action_params = [
        "query=" + encodeURI(window.editor.getValue()),
        "search_path=" +
          (from === "server" ? "public" : encodeURI($("#search_path").val()))
      ];

      if (jQuery("#paginate").is(":checked")) {
        action_params.push("paginate=on");
      }
    } else if (the_action === "find") {
      action_params = [
        "term=" + encodeURI($("#term").val()),
        "filter=" + encodeURI($("#filter").val())
      ];
    }
    return action_params;
  }

  $("#selectserver").on("change", function() {
    console.log("server changed to ", $(this).val());

    var base_location = location.href.split("&")[0],
      next_location = [base_location, "server=" + $(this).val()];

    next_location = next_location.concat(get_action_params("server"));

    location.href = next_location.join("&");
    //console.log('next_location would be',next_location.join('&'));
  });

  $("#selectdb").on("change", function() {
    console.log("database changed to ", $(this).val());

    var base_location = location.href.split("&")[0],
      next_location = [
        base_location,
        "server=" + $("#selectserver").val(),
        "database=" + $(this).val()
      ];

    next_location = next_location.concat(get_action_params("database"));

    location.href = next_location.join("&");
    //console.log('next_location would be',next_location.join('&'));
  });
});
