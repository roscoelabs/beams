/*!
 * Piwik - free/libre analytics platform
 *
 * Period selector screenshot tests.
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

describe("PeriodSelector", function () {
    this.timeout(0);

    var url = "?module=CoreHome&action=index&idSite=1&period=day&date=2012-01-01";

    it("should load correctly", function (done) {
        expect.screenshot("loaded").to.be.captureSelector('#periodString', function (page) {
            page.load(url);

            // disable broadcast.propagateNewPage & remove loading gif
            page.evaluate(function () {
                broadcast.propagateNewPage = function () {};
                $('#ajaxLoadingCalendar').remove();
            });
        }, done);
    });

    it("should expand when clicked", function (done) {
        expect.screenshot("expanded").to.be.captureSelector('#periodString', function (page) {
            page.click('.periodSelector');
        }, done);
    });

    it("should select a date when a date is clicked in day-period mode", function (done) {
        expect.screenshot("day_selected").to.be.captureSelector('#periodString', function (page) {
            page.click('.period-date .ui-datepicker-calendar a:contains(12)');
        }, done);
    });

    it("should change the month displayed when a month is selected in the month dropdown", function (done) {
        expect.screenshot("month_changed").to.be.captureSelector('#periodString', function (page) {
            page.evaluate(function () {
                $('.ui-datepicker-month').val(1).trigger('change');
            });
        }, done);
    });

    it("should change the year displayed when a year is selected in the year dropdown", function (done) {
        expect.screenshot("year_changed").to.be.captureSelector('#periodString', function (page) {
            page.evaluate(function () {
                $('.ui-datepicker-year').val(2013).trigger('change');
            });
        }, done);
    });

    it("should change the date when a date is clicked in week-period mode", function (done) {
        expect.screenshot("week_selected").to.be.captureSelector('#periodString', function (page) {
            page.click('label[for=period_id_week]');
            page.click('.period-date .ui-datepicker-calendar a:contains(13)');
        }, done);
    });

    it("should change the date when a date is clicked in month-period mode", function (done) {
        expect.screenshot("month_selected").to.be.captureSelector('#periodString', function (page) {
            page.click('label[for=period_id_month]');
            page.click('.period-date .ui-datepicker-calendar a:contains(14)');
        }, done);
    });

    it("should change the date when a date is clicked in year-period mode", function (done) {
        expect.screenshot("year_selected").to.be.captureSelector('#periodString', function (page) {
            page.click('label[for=period_id_year]');
            page.click('.period-date .ui-datepicker-calendar a:contains(15)');
        }, done);
    });

    it("should display the range picker when the range radio button is clicked", function (done) {
        expect.screenshot("range_picker_displayed").to.be.captureSelector('#periodString', function (page) {
            page.click('label[for=period_id_range]');
        }, done);
    });

    it("should change from & to dates when range picker calendar dates are clicked", function (done) {
        expect.screenshot("date_range_selected").to.be.captureSelector('#periodString', function (page) {
            page.click('#calendarFrom .ui-datepicker-calendar a:contains(10)');
            page.click('#calendarTo .ui-datepicker-calendar a:contains(18)');
            page.mouseMove('#calendarRangeApply');
        }, done);
    });
});