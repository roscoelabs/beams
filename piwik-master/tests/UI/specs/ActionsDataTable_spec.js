/*!
 * Piwik - free/libre analytics platform
 *
 * ActionsDataTable screenshot tests.
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

describe("ActionsDataTable", function () {
    this.timeout(0);

    var url = "?module=Widgetize&action=iframe&idSite=1&period=year&date=2012-08-09&moduleToWidgetize=Actions&actionToWidgetize=getPageUrls&isFooterExpandedInDashboard=1";

    it("should load correctly", function (done) {
        expect.screenshot('initial').to.be.capture(function (page) {
            page.load(url);
        }, done);
    });

    it("should sort column correctly when column header clicked", function (done) {
        expect.screenshot('column_sorted').to.be.capture(function (page) {
            page.click('th#avg_time_on_page');
        }, done);
    });

    it("should load subtables correctly when row clicked", function (done) {
        expect.screenshot('subtables_loaded').to.be.capture(function (page) {
            page.click('tr.subDataTable:first');
            page.click('tr.subDataTable:eq(2)');
        }, done);
    });

    it("should flatten table when flatten link clicked", function (done) {
        expect.screenshot('flattened').to.be.capture(function (page) {
            page.mouseMove('.tableConfiguration');
            page.click('.dataTableFlatten');
        }, done);
    });

    // Test is skipped as it randomly fails http://builds-artifacts.piwik.org/ui-tests.master/2433.1/screenshot-diffs/diffviewer.html
    it.skip("should exclude low population rows when exclude low population link clicked", function (done) {
        expect.screenshot('exclude_low_population').to.be.capture(function (page) {
            page.mouseMove('.tableConfiguration');
            page.click('.dataTableExcludeLowPopulation');
        }, done);
    });

    it("should load normal view when switch to view hierarchical view link is clicked", function (done) {
        expect.screenshot('unflattened').to.be.capture(function (page) {
            // exclude low population (copied from exclude_low_population test above as it was 'skipped')
            page.mouseMove('.tableConfiguration');
            page.click('.dataTableExcludeLowPopulation');

            page.mouseMove('.tableConfiguration');
            page.click('.dataTableFlatten');
        }, done);
    });

    it("should display pageview percentages when hovering over pageviews column", function (done) {
        expect.screenshot('pageview_percentages').to.be.capture(function (page) {
            page.mouseMove('tr:eq(2) td.column:eq(1)');
        }, done);
    });

    it("should generate a proper title for the visitor log segmented by the current row", function (done) {
        expect.screenshot('segmented_visitor_log_hover').to.be.capture(function (page) {
            var row = 'tr:eq(2) ';
            page.mouseMove(row + 'td.column:first');
            page.mouseMove(row + 'td.label .actionSegmentVisitorLog');
        }, done);
    });

    it("should open the visitor log segmented by the current row", function (done) {
        expect.screenshot('segmented_visitor_log').to.be.capture(function (page) {
            page.click('tr:eq(2) td.label .actionSegmentVisitorLog');
        }, done);
    });

    it("should display unique pageview percentages when hovering over unique pageviews column", function (done) {
        expect.screenshot('unique_pageview_percentages').to.be.capture(function (page) {
            page.click('.ui-widget .ui-dialog-titlebar-close');

            page.mouseMove('tr:eq(2) td.column:eq(2)');
        }, done);
    });

    it("should search through table when search input entered and search button clicked", function (done) {
        expect.screenshot('search').to.be.capture(function (page) {
            page.sendKeys('.dataTableSearchPattern>input[type=text]', 'i');
            page.click('.dataTableSearchPattern>input[type=submit]');
        }, done);
    });
    
    it("should automatically expand subtables if it contains only one folder", function (done) {
        expect.screenshot('auto_expand').to.be.capture(function (page) {
            page.load(url + '&viewDataTable=table');
            page.click('tr .value:contains("blog")');
            page.click('tr .value:contains("2012")');
        }, done);
    });
});
