/** globals stateObj */
function historyApiBack() {
  window.history && window.history.back();
}

function redirectToIframesView() {
  var redirect_to,
    subject = stateObj.pathname
      //.replace(stateObj.subfolder, '')
      .replace(`/src/views`, '')
      .replace('.php', '');

  if (subject.includes('/redirect/server')) {
    subject = subject.replace('/redirect/server', '/servers');
  }
  subject += location.search;
  redirect_to = `${subject}`;
  var redirection_msg =
    'location subject is ' + subject + ' will redirect_to ' + redirect_to;
  console.log(
    `redirect: ${stateObj.pathname}${location.search} -> ${redirect_to}`
  );
  return redirect_to;
}

$.ready
  .then(() => {
    stateObj.dttArgs = {
      pageLength: 100,
    };
    // Need to open popup from parent document
    stateObj.basePath =
      window.parent.location.origin + (stateObj.subfolder || '');

    if (stateObj.serverSide) {
      // @ts-ignore
      let dttData = [...new URLSearchParams($('#sqlform').serialize())].reduce(
        (accum, [key, value]) => {
          accum[key] = value;
          return accum;
        },
        {}
      );
      dttData.json = 'on';
      stateObj.dttArgs = {
        ...stateObj.dttArgs,
        columns: stateObj.dttFields,
        processing: !!stateObj.serverSide,
        serverSide: !!stateObj.serverSide,
        ajax: !stateObj.serverSide
          ? ''
          : {
              url: $('#sqlform').attr('action'),
              method: 'POST',
              data: dttData,
            },
      };
    }
    let redirect_to = redirectToIframesView();
    window.parent.document.title = window.document.title;

    /* beautify preserve:start */
    stateObj.realurl = location.href; //.replace(location.origin, '');
    //path will only be defined inside a route

    /* beautify preserve:end */
    stateObj.redirect_to = redirect_to;
    stateObj.parenturl = window.parent.location.href;
    if (window.location.href.indexOf('servers?action=logout') !== -1) {
      window.setTimeout(function () {
        window.parent.location.replace(`${stateObj.basePath}/servers`);
      }, 3000);
      
    }
    
  
      if (
        stateObj.method === 'GET' &&
        stateObj.redirect_to !== stateObj.parenturl
      ) {
        let { reload } = stateObj || {};
        console.log('will pushState. Reload is ' + reload, stateObj);
        window.parent.history.pushState(
          stateObj,
          document.title,
          stateObj.redirect_to
        );
      }
      return { jqFn: jQuery.fn, hljsFn: globalThis.hljs };
   
  })
  .then(({ jqFn, hljsFn }) => {
    if (jqFn.select2) {
      jQuery('.select2').select2();
    }
    if (jqFn.DataTable) {
      stateObj.dataTable = $('.will_be_datatable').DataTable(stateObj.dttArgs);
    }

    if (hljsFn.highlightBlock) {
      $('pre code.hljs').each(function (i, block) {
        hljsFn.highlightBlock(block);
      });
    }
    let { reload } = stateObj || {};
    if (reload && reload !== 'none') {
      globalThis.postMessage(
        { reload_browser: true },
        window.parent.location.origin
      );
    }

    return stateObj;
  })
  .catch((err) => {
    console.error(err);
  });
