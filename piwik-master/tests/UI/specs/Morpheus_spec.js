/*!
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

describe("Morpheus", function () {
    this.timeout(0);

    var url = "?module=Morpheus&action=demo";

    before(function () {
        // Enable development mode
        testEnvironment.configOverride = {
            Development: {
                enabled: true
            }
        };
        testEnvironment.save();
    });

    it("should show all UI components and CSS classes", function (done) {
        expect.screenshot('load').to.be.capture(function (page) {
            page.load(url);
        }, done);
    });
});
