if (!window.inPopUp) {
  if (stateObj.reload !== "none" && parent.brames && parent.frames.browser) {
    parent.frames.browser.location.replace(
      `${stateObj.subfolder}/src/views/browser`
    );
  }

  if (
    stateObj.reload === "other" &&
    !parent.frames.length &&
    stateObj.in_test === "0"
  ) {
    var destination = location.href.replace("/src/views", "");
    console.log("will do location replace", destination);
    location.replace(destination);
  }
  [
    ...window.parent.document
      .querySelector("#detail")
      .contentDocument.querySelectorAll(".toplink a.toplink_popup")
  ].forEach(element => {
    let href = element.href;
    element.addEventListener("click", e => {
      e.preventDefault();
      window
        .open(
          `${href}`,
          `sqledit:${stateObj.server}`,
          "toolbar=no,width=750,height=520,resizable=yes,scrollbars=yes"
        )
        .focus();
    });
    element.setAttribute("rel", href);

    element.href = "javascript:void(this.click())"; // eslint-disable-line
  });
  $(document).ready(function() {
    $("#toplink_logout").click(function(e) {
      e.preventDefault();
      if (confirm(stateObj.strconfdropcred)) {
        window.location.href = $(this).attr("href");
      }
    });

    // Check if we are inside an iframe, in which case use history API to change top URL
    if (window.parent.frames.length !== 0) {
      window.parent.document.title = window.document.title;

      /* beautify preserve:start */
      stateObj.realurl = location.href.replace(location.origin, "");
      //path will only be defined inside a route

      /* beautify preserve:end */
      stateObj.newurl =
        stateObj.subfolder +
        stateObj.realurl
          .replace(stateObj.subfolder, "")
          .replace("src/views/", "")
          .replace(".php", "");
      stateObj.parenturl = window.parent.location.href.replace(
        window.parent.location.origin,
        ""
      );

      if (window.location.href.indexOf("servers?action=logout") !== -1) {
        window.setTimeout(function() {
          window.parent.location.replace(`${stateObj.subfolder}/servers`);
        }, 3000);
      } else if (
        stateObj.method === "GET" &&
        stateObj.newurl !== stateObj.parenturl
      ) {
        //console.log('will pushState', stateObj);
        window.parent.history.pushState(
          stateObj,
          document.title,
          stateObj.newurl
        );
      }
      /* else if (stateObj.method === 'POST') {
              console.log('POST', stateObj.newurl);
            }*/

      if (jQuery.fn) {
        if (jQuery.fn.select2) {
          jQuery(".select2").select2();
        }
        if (jQuery.fn.DataTable) {
          $(".will_be_datatable").DataTable({
            pageLength: 100
          });
        }
      }
      if (typeof hljs !== "undefined") {
        $("pre code.hljs").each(function(i, block) {
          hljs.highlightBlock(block);
        });
      }
    } else if (window.stateObj.in_test === "0") {
      var redirect_to,
        subject = location.pathname
          .replace(stateObj.subfolder, "")
          .replace("/src/views/", "")
          .replace(".php", "");

      if (subject === "/redirect/server") {
        subject = "/servers";
      }
      redirect_to = `${stateObj.subfolder}/${subject}/${location.search}`;
      var redirection_msg =
        "location subject " + subject + " will redirect_to " + redirect_to;
      //console.log(redirection_msg);
      location.replace(redirect_to);
    }
  });
}
