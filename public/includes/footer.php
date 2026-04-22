          <footer class="footer">
            <div class="row g-0 justify-content-between fs-10 mt-4 mb-3">
              <div class="col-12 col-sm-auto text-center">
                <p class="mb-0 text-600">isp360 Copyright 2026 &copy; mostafaJoy</p>
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
    <?php
    $appPathPrefix = isset($appBasePath) ? (string) $appBasePath : '';
    if ($appPathPrefix === '/') {
      $appPathPrefix = '';
    }
    ?>
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

        // Keep existing header markup intact; do not auto-generate Overview/search/actions toolbar.
      })();
    </script>
    <script src="<?= $appPathPrefix ?>/assets/js/theme.js"></script>
  </body>

</html>

