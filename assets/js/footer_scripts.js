function redirectToIframesView() {
  if (
    window.inPopUp ||
    parent.frames.length ||
    stateObj.reload === 'other' ||
    stateObj.in_test !== '0'
  ) {
    return false;
  }
  var redirect_to,
    subject = location.pathname
      .replace(stateObj.subfolder, '')
      .replace('/src/views/', '')
      .replace('.php', '');

  if (subject === '/redirect/server') {
    subject = '/servers';
  }
  redirect_to = `${stateObj.subfolder}/${subject}${location.search}`;
  var redirection_msg =
    'location subject ' + subject + ' will redirect_to ' + redirect_to;
  return redirect_to;
}
function addBehaviorToTopLinks(amIDetailFrame) {
  const parentHandle =
      amIDetailFrame && window.parent.document.querySelector('#detail'),
    toplink_logout =
      amIDetailFrame &&
      parentHandle.contentDocument.querySelector('#toplink_logout');

  parentHandle &&
    [
      ...parentHandle.contentDocument.querySelectorAll(
        '.toplink a.toplink_popup'
      ),
    ].forEach(element => {
      let href = element.href;
      element.addEventListener('click', e => {
        e.preventDefault();
        window
          .open(
            `${href}`,
            `sqledit:${stateObj.server}`,
            'toolbar=no,width=750,height=520,resizable=yes,scrollbars=yes'
          )
          .focus();
      });
      element.setAttribute('rel', href);

      element.href = 'javascript:void(this.click())'; // eslint-disable-line
    });
  toplink_logout &&
    toplink_logout.addEventListener('click', e => {
      e.preventDefault();
      if (confirm(stateObj.strconfdropcred)) {
        window.location.href = e.target.href;
      }
    });

  return;
}
if (
  !window.inPopUp &&
  stateObj.reload !== 'other' &&
  !parent.frames.length &&
  stateObj.in_test === '0'
) {
  redirectToIframesView();
}
$.ready
  .then(() => {
    let amIDetailFrame = document.body.classList.contains('detailbody');
    // Need to open popup from parent document

    let redirect_to = redirectToIframesView();
    if (redirect_to === false) {
      return addBehaviorToTopLinks(amIDetailFrame);
    }
    window.location.replace(redirect_to);
  })
  .then(() => {
    if (window.parent.frames.length === 0) {
      return;
    }
    window.parent.document.title = window.document.title;

    /* beautify preserve:start */
    stateObj.realurl = location.href.replace(location.origin, '');
    //path will only be defined inside a route

    /* beautify preserve:end */
    stateObj.newurl =
      stateObj.subfolder +
      stateObj.realurl
        .replace(stateObj.subfolder, '')
        .replace('src/views/', '')
        .replace('.php', '');
    stateObj.parenturl = window.parent.location.href.replace(
      window.parent.location.origin,
      ''
    );
    return;
  })
  .then(() => {
    if (window.location.href.indexOf('servers?action=logout') !== -1) {
      window.setTimeout(function() {
        window.parent.location.replace(`${stateObj.subfolder}/servers`);
      }, 3000);
    } else if (
      stateObj.method === 'GET' &&
      stateObj.newurl !== stateObj.parenturl
    ) {
      //console.log('will pushState', stateObj);
      window.parent.history.pushState(
        stateObj,
        document.title,
        stateObj.newurl
      );
    }
  })
  .then(() => {
    if (jQuery.fn) {
      if (jQuery.fn.select2) {
        jQuery('.select2').select2();
      }
      if (jQuery.fn.DataTable) {
        $('.will_be_datatable').DataTable({
          pageLength: 100,
        });
      }
    }
    if (typeof hljs !== 'undefined') {
      $('pre code.hljs').each(function(i, block) {
        hljs.highlightBlock(block);
      });
    }
    return;
  })
  .catch(err => {
    console.error(err);
  });
