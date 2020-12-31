function shouldSkipRedirection() {
  return (
    window.inPopUp ||
    parent.frames.length ||
    stateObj.reload === 'other' ||
    stateObj.in_test !== '0'
  );
}

function addBehaviorToTopLinks(amIDetailFrame) {
  const parentHandle =
      amIDetailFrame && window.parent.document.querySelector('#detail'),
    toplink_logout =
      amIDetailFrame &&
      (parentHandle.contentDocument || document).querySelector(
        '#toplink_logout'
      );

  parentHandle &&
    [
      ...(parentHandle.contentDocument || document).querySelectorAll(
        '.toplink a.toplink_popup'
      ),
    ].forEach((element) => {
      let href = element.href;
      element.addEventListener('click', (e) => {
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
    toplink_logout.addEventListener('click', (e) => {
      e.preventDefault();
      if (confirm(stateObj.strconfdropcred)) {
        window.location.href = e.target.href;
      }
    });

  return;
}

$.ready.then(() => {
  let amIDetailFrame = document.body.classList.contains('detailbody');
  if (shouldSkipRedirection()) {
    return addBehaviorToTopLinks(amIDetailFrame);
  }
});
