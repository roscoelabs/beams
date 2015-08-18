/*!
 * Piwik - free/libre analytics platform
 *
 * Installation screenshot tests.
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
var fs = require('fs');

describe("Installation", function () {
    this.timeout(0);

    this.fixture = null;

    before(function () {
        testEnvironment.testUseMockAuth = 0;
        testEnvironment.configFileLocal = path.join(PIWIK_INCLUDE_PATH, "/tmp/test.config.ini.php");
        testEnvironment.dontUseTestConfig = true;
        testEnvironment.tablesPrefix = 'piwik_';
        testEnvironment.save();

        if (fs.exists(testEnvironment.configFileLocal)) {
            fs.remove(testEnvironment.configFileLocal);
        }
    });

    after(function () {
        delete testEnvironment.configFileLocal;
        delete testEnvironment.dontUseTestConfig;
        delete testEnvironment.tablesPrefix;
        delete testEnvironment.testUseMockAuth;
        testEnvironment.save();
    });

    it("should display an error message when trying to access a resource w/o a config.ini.php file", function (done) {
        expect.screenshot("access_no_config").to.be.capture(function (page) {
            page.load("?module=CoreHome&action=index&ignoreClearAllViewDataTableParameters=1");
        }, done);
    });

    it("should start the installation process when the index is visited w/o a config.ini.php file", function (done) {
        expect.screenshot("start").to.be.capture(function (page) {
            page.load("?ignoreClearAllViewDataTableParameters=1");
        }, done);
    });

    it("should display the system check page when next is clicked on the first page", function (done) {
        expect.screenshot("system_check").to.be.capture(function (page) {
            page.click('.next-step .btn');
        }, done);
    });

    it("should display the database setup page when next is clicked on the system check page", function (done) {
        expect.screenshot("db_setup").to.be.capture(function (page) {
            page.click('.next-step .btn');
        }, done);
    });

    it("should fail when the next button is clicked and no database info is entered in the form", function (done) {
        expect.screenshot("db_setup_fail").to.be.capture(function (page) {
            page.click('.btn');
        }, done);
    });

    it("should display the tables created page when next is clicked on the db setup page w/ correct info entered in the form", function (done) {
        expect.screenshot("db_created").to.be.capture(function (page) {
            var dbInfo = testEnvironment.readDbInfoFromConfig();
            var username = dbInfo.username;
            var password = dbInfo.password;

            page.sendKeys('input[name=username]', username);

            if (password) {
                page.sendKeys('input[name=password]', password);
            }

            page.sendKeys('input[name=dbname]', 'newdb');
            page.click('.btn');
        }, done);
    });

    it("should display the superuser configuration page when next is clicked on the tables created page", function (done) {
        expect.screenshot("superuser").to.be.capture(function (page) {
            page.click('.next-step .btn');
        }, done);
    });

    it("should fail when incorrect information is entered in the superuser configuration page", function (done) {
        expect.screenshot("superuser_fail").to.be.capture(function (page) {
            page.click('.btn');
        }, done);
    });

    it("should display the setup a website page when next is clicked on the filled out superuser config page", function (done) {
        expect.screenshot("setup_website").to.be.capture(function (page) {
            page.sendKeys('input[name=login]', 'thesuperuser');
            page.sendKeys('input[name=password]', 'thepassword');
            page.sendKeys('input[name=password_bis]', 'thepassword');
            page.sendKeys('input[name=email]', 'hello@piwik.org');
            page.click('.btn');
            page.wait(3000);
        }, done);
    });

    it("should should fail when incorrect information is entered in the setup a website page", function (done) {
        expect.screenshot("setup_website_fail").to.be.capture(function (page) {
            page.click('.btn');
        }, done);
    });

    it("should display the javascript tracking page when correct information is entered in the setup website page and next is clicked", function (done) {
        expect.screenshot("js_tracking").to.be.capture(function (page) {
            page.sendKeys('input[name=siteName]', 'Serenity');
            page.sendKeys('input[name=url]', 'serenity.com');
            page.evaluate(function () {
                $('select[name=timezone]').val('Europe/Paris');
                $('select[name=ecommerce]').val('1');
            });
            page.click('.btn');
            page.wait(3000);
        }, done);
    });

    it("should display the congratulations page when next is clicked on the javascript tracking page", function (done) {
        expect.screenshot("congrats").to.be.capture(function (page) {
            page.click('.next-step .btn');
        }, done);
    });

    it("should continue to piwik after submitting on the privacy settings form in the congrats page", function (done) {
        expect.screenshot('login_form', 'Login').to.be.capture(function (page) {
            page.click('.btn');
        }, done);
    });
});