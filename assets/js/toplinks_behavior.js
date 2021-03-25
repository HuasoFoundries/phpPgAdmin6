/// <reference lib="dom" />

function shouldSkipRedirection() {
  
  return (
    window.inPopUp ||
    parent.frames.length ||
    stateObj.reload === 'other' ||
    stateObj.in_test !== '0' ||
    parent.location.href === location.href
  );
}

$.ready.then(() => {
   
  
  document.querySelectorAll(
    '.toplinks .toplink_popup'
  ).forEach((element) => {
      element.addEventListener('click', (e) => {
        window.name = 'detail';
        window
          .open(
            `${element.getAttribute('rel')}`,
            '_blank', //`sqledit:${stateObj.server}`,
            'toolbar=no,width=750,height=520,resizable=yes,scrollbars=yes'
          )
          .focus();
      });
    });
    document.querySelector(
      '#toplink_logout'
    ).addEventListener('click', (e) => {
      e.preventDefault();
      if (confirm(stateObj.strconfdropcred)) {
        window.location.href = e.target.getAttribute('rel');
      }
    });

  return;
 
 
});
