          <footer class="footer">
            <div class="row g-0 justify-content-between fs-10 mt-4 mb-3">
              <div class="col-12 col-sm-auto text-center">
                <p class="mb-0 text-600">isp360 Copyright &copy; 2026 @mostafaJoy</p>
              </div>
              <div class="col-12 col-sm-auto text-center">
                <p class="mb-0 text-600">1.0.0</p>
              </div>
            </div>
          </footer>
      </div>
    </main>
    <!-- ===============================================--><!--    End of Main Content--><!-- ===============================================-->

    <!-- ===============================================--><!--    JavaScripts--><!-- ===============================================-->
    <?php $appPathPrefix = isset($appBasePath) ? $appBasePath : ''; ?>
    <script src="<?= $appPathPrefix ?>/vendors/popper/popper.min.js"></script>
    <script src="<?= $appPathPrefix ?>/vendors/bootstrap/bootstrap.min.js"></script>
    <script src="<?= $appPathPrefix ?>/vendors/anchorjs/anchor.min.js"></script>
    <script src="<?= $appPathPrefix ?>/vendors/is/is.min.js"></script>
    <script src="<?= $appPathPrefix ?>/vendors/countup/countUp.umd.js"></script>
    <script src="<?= $appPathPrefix ?>/vendors/echarts/echarts.min.js"></script>
    <script src="<?= $appPathPrefix ?>/vendors/dayjs/dayjs.min.js"></script>
    <script src="<?= $appPathPrefix ?>/vendors/lodash/lodash.min.js"></script>
    <script src="<?= $appPathPrefix ?>/vendors/list.js/list.min.js"></script>
    <script src="<?= $appPathPrefix ?>/vendors/fontawesome/all.min.js"></script>
    <script src="<?= $appPathPrefix ?>/vendors/prism/prism.js"></script>
    <script>
      (function () {
        var path = window.location.pathname.toLowerCase();
        var appPrefix = '<?= strtolower($appPathPrefix) ?>/app/';
        if (path.indexOf(appPrefix) === -1) return;

        var content = document.querySelector('.content');
        if (!content) return;

        var titleNode = content.querySelector('.page-header-title, h1');
        var pageTitle = titleNode ? titleNode.textContent.trim() : 'Overview';

        var legacyBreadcrumbs = content.querySelectorAll(':scope > nav[aria-label="breadcrumb"]');
        legacyBreadcrumbs.forEach(function (node) {
          node.remove();
        });

        var legacyHeaders = content.querySelectorAll(':scope > .page-header, :scope > .page-header.mb-4');
        legacyHeaders.forEach(function (node) {
          node.remove();
        });

        var card = content.querySelector(':scope > .card, :scope > .row .card');
        if (!card || card.closest('.dropdown-menu')) return;

        var sectionActions = [
          {
            key: '/support-desk/',
            buttons: [
              { label: 'New Ticket', icon: 'fas fa-plus' },
              { label: 'Export', icon: 'fas fa-download' }
            ]
          },
          {
            key: '/inventory/',
            buttons: [
              { label: 'Add Item', icon: 'fas fa-plus' },
              { label: 'Import', icon: 'fas fa-file-import' }
            ]
          },
          {
            key: '/partners/',
            buttons: [
              { label: 'Add Partner', icon: 'fas fa-user-plus' },
              { label: 'Statement', icon: 'fas fa-file-invoice' }
            ]
          },
          {
            key: '/finance/',
            buttons: [
              { label: 'Add Entry', icon: 'fas fa-plus-circle' },
              { label: 'Export', icon: 'fas fa-file-export' }
            ]
          },
          {
            key: '/hr-payroll/',
            buttons: [
              { label: 'Add Employee', icon: 'fas fa-user-plus' },
              { label: 'Run Payroll', icon: 'fas fa-play' }
            ]
          },
          {
            key: '/reports/',
            buttons: [
              { label: 'Generate', icon: 'fas fa-chart-line' },
              { label: 'Export', icon: 'fas fa-file-export' }
            ]
          },
          {
            key: '/logs/',
            buttons: [
              { label: 'Filter', icon: 'fas fa-filter' },
              { label: 'Export', icon: 'fas fa-download' }
            ]
          },
          {
            key: '/administration/',
            buttons: [
              { label: 'Add New', icon: 'fas fa-plus' },
              { label: 'Settings', icon: 'fas fa-cog' }
            ]
          }
        ];

        var selected = sectionActions.find(function (entry) {
          return path.indexOf(entry.key) !== -1;
        });

        var actions = selected ? selected.buttons : [
          { label: 'Add New', icon: 'fas fa-plus' },
          { label: 'Export', icon: 'fas fa-download' }
        ];

        var navRoot = document.getElementById('navbarVerticalNav');
        var activeLink = navRoot ? navRoot.querySelector('a.nav-link.active[href]:not([href^="#"])') : null;
        var activeLabel = activeLink ? activeLink.textContent.replace(/\s+/g, ' ').trim() : pageTitle;
        var parentCollapse = activeLink ? activeLink.closest('ul.collapse') : null;
        var parentToggle = parentCollapse && parentCollapse.id && navRoot
          ? navRoot.querySelector('a.nav-link.dropdown-indicator[href="#' + parentCollapse.id + '"]')
          : null;
        var parentLabel = parentToggle ? parentToggle.textContent.replace(/\s+/g, ' ').trim() : '';

        var breadcrumbItems = [{ label: 'Home', href: '<?= $appPathPrefix ?>/index.php', icon: 'fas fa-home', iconOnly: true }];
        if (parentLabel) {
          breadcrumbItems.push({ label: parentLabel });
        }
        if (!parentLabel || activeLabel !== parentLabel) {
          breadcrumbItems.push({ label: activeLabel || pageTitle });
        }

        var buildBreadcrumb = function () {
          var wrapper = document.createElement('div');
          wrapper.className = 'generated-page-breadcrumb mb-1';

          var list = document.createElement('div');
          list.className = 'd-flex flex-wrap align-items-center gap-2 fs-10 text-600';

          breadcrumbItems.forEach(function (item, index) {
            var crumb = document.createElement(index === 0 && item.href ? 'a' : 'span');
            crumb.className = 'text-600';
            if (item.href) {
              crumb.href = item.href;
              crumb.classList.add('text-decoration-none');
            }
            if (item.icon) {
              crumb.innerHTML = item.iconOnly
                ? '<span class="' + item.icon + '"></span><span class="visually-hidden">' + item.label + '</span>'
                : '<span class="' + item.icon + ' me-1"></span>' + item.label;
            } else {
              crumb.textContent = item.label;
            }
            list.appendChild(crumb);

            if (index < breadcrumbItems.length - 1) {
              var sep = document.createElement('span');
              sep.className = 'text-400';
              sep.innerHTML = '<span class="fas fa-chevron-right fs-11"></span>';
              list.appendChild(sep);
            }
          });

          wrapper.appendChild(list);
          return wrapper;
        };

        var cardAnchor = card;
        var anchorWalker = card;
        while (anchorWalker.parentElement && anchorWalker.parentElement !== content) {
          anchorWalker = anchorWalker.parentElement;
        }
        if (anchorWalker.parentElement === content) {
          cardAnchor = anchorWalker;
        }

        var staleBreadcrumbs = content.querySelectorAll('.generated-card-breadcrumb, .generated-page-breadcrumb');
        staleBreadcrumbs.forEach(function (node) {
          node.remove();
        });

        cardAnchor.insertAdjacentElement('beforebegin', buildBreadcrumb());

        var existingHeader = card.querySelector(':scope > .card-header');
        if (existingHeader) {
          var oldInlineBreadcrumb = existingHeader.querySelector('.generated-card-breadcrumb, .generated-page-breadcrumb');
          if (oldInlineBreadcrumb) {
            oldInlineBreadcrumb.remove();
          }
        } else {
          var header = document.createElement('div');
          header.className = 'card-header border-bottom border-200 d-flex flex-wrap justify-content-between align-items-start gap-3';

          var titleBlock = document.createElement('div');
          titleBlock.className = 'd-flex flex-column';

          var title = document.createElement('h5');
          title.className = 'mb-0';
          title.textContent = pageTitle;
          titleBlock.appendChild(title);

          var actionWrap = document.createElement('div');
          actionWrap.className = 'd-flex flex-wrap gap-2';

          actions.forEach(function (action, index) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = index === 0 ? 'btn btn-primary btn-sm' : 'btn btn-falcon-default btn-sm';
            btn.innerHTML = '<span class="' + action.icon + ' me-1"></span>' + action.label;
            actionWrap.appendChild(btn);
          });

          header.appendChild(titleBlock);
          header.appendChild(actionWrap);
          card.insertBefore(header, card.firstChild);

          var bodyTitle = card.querySelector('.card-body .card-title');
          if (bodyTitle) {
            bodyTitle.remove();
          }
        }

        var pageFooter = content.querySelector('footer.footer');
        if (pageFooter) {
          pageFooter.classList.add('mt-3');
          cardAnchor.insertAdjacentElement('afterend', pageFooter);
        }

        if (existingHeader) {
          return;
        }

        card.setAttribute('data-generated-card-view', 'table');

        var table = card.querySelector(':scope > .card-body table, :scope > table');
        var cardBody = card.querySelector(':scope > .card-body');

        if (table) {
          table.classList.add('table', 'table-sm', 'mb-0', 'fs-10', 'generated-card-table');

          var tableHead = table.querySelector('thead');
          if (tableHead) {
            tableHead.classList.add('bg-body-tertiary');
            tableHead.querySelectorAll('th').forEach(function (th) {
              th.classList.add('text-800', 'align-middle');
            });
          }

          table.querySelectorAll('tbody td').forEach(function (td) {
            td.classList.add('align-middle');
          });
        }

        if (cardBody) {
          cardBody.classList.add('p-0');

          if (table && table.parentElement === cardBody) {
            var tableWrap = document.createElement('div');
            tableWrap.className = 'table-responsive scrollbar';
            table.parentElement.insertBefore(tableWrap, table);
            tableWrap.appendChild(table);
          }
        }

        var headerTitle = header.querySelector('h5');
        var actionWrap = header.querySelector('.d-flex.flex-wrap.gap-2');
        header.className = 'card-header border-bottom border-200 px-0';

        var headerLayout = document.createElement('div');
        headerLayout.className = 'd-lg-flex justify-content-between';

        var leftGroup = document.createElement('div');
        leftGroup.className = 'row flex-between-center gy-2 px-x1';

        var titleCol = document.createElement('div');
        titleCol.className = 'col-auto pe-0';
        if (headerTitle) {
          headerTitle.className = 'mb-0';
          headerTitle.tagName;
          titleCol.appendChild(headerTitle);
        }

        var searchCol = document.createElement('div');
        searchCol.className = 'col-auto';
        searchCol.innerHTML = '<form><div class="input-group input-search-width"><input class="form-control form-control-sm shadow-none generated-table-search" type="search" placeholder="Search" aria-label="search"><button class="btn btn-sm btn-outline-secondary border-300 hover-border-secondary" type="button"><span class="fa fa-search fs-10"></span></button></div></form>';

        leftGroup.appendChild(titleCol);
        leftGroup.appendChild(searchCol);

        var divider = document.createElement('div');
        divider.className = 'border-bottom border-200 my-3';

        var rightGroup = document.createElement('div');
        rightGroup.className = 'd-flex align-items-center justify-content-between justify-content-lg-end px-x1';

        var controlWrap = document.createElement('div');
        controlWrap.className = 'd-flex align-items-center';

        var viewDropdown = document.createElement('div');
        viewDropdown.className = 'dropdown';
        viewDropdown.innerHTML = '<button class="btn btn-sm btn-falcon-default dropdown-toggle dropdown-caret-none" type="button" data-bs-toggle="dropdown" data-boundary="window" aria-expanded="false"><span class="d-none d-sm-inline-block d-xl-none d-xxl-inline-block me-1">Table View</span><span class="fas fa-chevron-down" data-fa-transform="shrink-3 down-1"></span></button><div class="dropdown-menu dropdown-toggle-item dropdown-menu-end border py-2"><span class="dropdown-item active">Table View</span></div>';

        controlWrap.appendChild(viewDropdown);

        if (actionWrap) {
          Array.from(actionWrap.children).forEach(function (button, index) {
            button.className = index === 0
              ? 'btn btn-falcon-default btn-sm mx-2'
              : 'btn btn-falcon-default btn-sm';
            controlWrap.appendChild(button);
          });
        }

        var moreDropdown = document.createElement('div');
        moreDropdown.className = 'dropdown font-sans-serif ms-2';
        moreDropdown.innerHTML = '<button class="btn btn-falcon-default text-600 btn-sm dropdown-toggle dropdown-caret-none" type="button" data-bs-toggle="dropdown" data-boundary="viewport" aria-expanded="false"><span class="fas fa-ellipsis-h fs-11"></span></button><div class="dropdown-menu dropdown-menu-end border py-2"><a class="dropdown-item" href="#">View</a><a class="dropdown-item" href="#">Export</a><div class="dropdown-divider"></div><a class="dropdown-item text-danger" href="#">Remove</a></div>';
        controlWrap.appendChild(moreDropdown);

        rightGroup.appendChild(controlWrap);

        headerLayout.appendChild(leftGroup);
        headerLayout.appendChild(divider);
        headerLayout.appendChild(rightGroup);

        header.innerHTML = '';
        header.appendChild(headerLayout);

        var searchInput = header.querySelector('.generated-table-search');
        if (searchInput && table) {
          searchInput.addEventListener('input', function () {
            var query = searchInput.value.trim().toLowerCase();
            table.querySelectorAll('tbody tr').forEach(function (row) {
              var text = row.textContent.toLowerCase();
              row.style.display = !query || text.indexOf(query) !== -1 ? '' : 'none';
            });
          });
        }
      })();
    </script>
    <script src="<?= $appPathPrefix ?>/assets/js/theme.js"></script>
  </body>

</html>

