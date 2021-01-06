window.jsTree = $('#lazy').jstree({
  state: {
    key: 'jstree',
  },
  plugins: ['state'],
  core: {
    data: {
      url: function (node) {
        let leafs_url;
        if (node.id === '#') {
          leafs_url = stateObj.subfolder + '/browser?action=tree';
        } else {
          leafs_url = node.original.url;
        }
        console.log({ leafs_url });
        return leafs_url;
      },
    },
  },
});
$('#refreshTree').on('click', () => {
  if (window.jsTree.jstree) {
    window.jsTree.jstree('refresh');
  } else if (window.jsTree.refresh) {
    window.jsTree.refresh();
  }
});

if (parent.frames && parent.frames.detail) {
  parent.frames.detail.jsTree = window.jsTree;
}

$('#lazy').on('activate_node.jstree', function (e, data) {
  if (window.parent.frames.detail) {
    let { frameLocation } = window.parent.frames.detail,
      nextLocation = data.node.a_attr.href;
    console.log({ nextLocation });
    (frameLocation || globalThis.location).replace(nextLocation);
  }
});
$('#lazy').on('state_ready.jstree', function (e, data) {
  console.log('state_ready');
  const detailContailer = $('#detail');
  $.ready.then(() => {
    jQuery('#browser_container').resizableSafe({
      handleSelector: '.splitter',
      resizeHeight: false,
      //resizeWidthFrom: 'left',
      onDragEnd: function (e, $el, opt) {
        let currentWidth = $el.width(),
          detailWidth = window.innerWidth - currentWidth;
        console.log('onDragEnd', { e, opt, $el, currentWidth, detailWidth });
        detailContailer.width(detailWidth);

        // explicitly return **false** if you don't want
        // auto-height computation to occur
      },
      onDrag: function (e, $el, newWidth, newHeight, opt) {
        // limit box size

        if (newWidth > 350) {
          newWidth = 350;
          $el.width(newWidth);
          return false;
        }

        // explicitly return **false** if you don't want
        // auto-height computation to occur
        //return false;
      },
    });
  });
});
$('#lazy').on('loaded.jstree', function (e, data) {
  console.log('loaded');
  $('#lazy').data('jstree').show_dots();
});
$('#lazy').on('click', '.jstree-anchor', function () {
  console.log(this);
});

window.addEventListener(
  'message',
  (event) => {
    console.log(event);

    const { origin, isTrusted, data } = event,
      { reload_browser } = data || {},
      { jsTree } = globalThis || {};

    console.log({ reload_browser, jsTree });
    if (!isTrusted) {
      console.warn('non trusted event');
      return;
    }
    if (origin !== location.origin) {
      console.warn('different origin', { origin, location });
      return;
    }

    if (data.reload_browser && globalThis.jsTree) {
      try {
        if (window.jsTree.jstree) {
          window.jsTree.jstree('refresh');
        } else if (window.jsTree.refresh) {
          window.jsTree.refresh();
        }
      } catch (err) {
        console.warn(err);
      }
    }
  },
  false
);
