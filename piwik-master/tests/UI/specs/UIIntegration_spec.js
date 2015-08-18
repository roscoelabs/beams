/*!
 * Piwik - free/libre analytics platform
 *
 * Screenshot integration tests.
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

describe("UIIntegrationTest", function () { // TODO: Rename to Piwik?
    this.timeout(0);

    var generalParams = 'idSite=1&period=year&date=2012-08-09',
        idSite2Params = 'idSite=2&period=year&date=2012-08-09',
        evolutionParams = 'idSite=1&period=day&date=2012-01-31&evolution_day_last_n=30',
        urlBase = 'module=CoreHome&action=index&' + generalParams,
        widgetizeParams = "module=Widgetize&action=iframe",
        segment = encodeURIComponent("browserCode==FF") // from OmniFixture
        ;

    before(function (done) {
        testEnvironment.queryParamOverride = {
            forceNowValue: testEnvironment.forcedNowTimestamp,
            visitorId: testEnvironment.forcedIdVisitor,
            realtimeWindow: 'false'
        };
        testEnvironment.save();

        testEnvironment.callApi("SitesManager.setSiteAliasUrls", {idSite: 3, urls: []}, done);
    });

    beforeEach(function () {
        delete testEnvironment.configOverride;
        testEnvironment.testUseMockAuth = 1;
        testEnvironment.save();
    });

    after(function () {
        delete testEnvironment.queryParamOverride;
        testEnvironment.testUseMockAuth = 1;
        testEnvironment.save();
    });

    // dashboard tests
    it("should load dashboard1 correctly", function (done) {
        expect.screenshot("dashboard1").to.be.captureSelector('.pageWrap,.expandDataTableFooterDrawer', function (page) {
            page.load("?" + urlBase + "#" + generalParams + "&module=Dashboard&action=embeddedIndex&idDashboard=1");

            page.evaluate(function () {
                // Prevent random sizing error eg. http://builds-artifacts.piwik.org/ui-tests.master/2301.1/screenshot-diffs/diffviewer.html
                $("[widgetid=widgetActionsgetOutlinks] .widgetContent").text('Displays different at random -> hidden');
            });
        }, done);
    });

    it("should load dashboard2 correctly", function (done) {
        expect.screenshot("dashboard2").to.be.captureSelector('.pageWrap,.expandDataTableFooterDrawer', function (page) {
            page.load("?" + urlBase + "#" + generalParams + "&module=Dashboard&action=embeddedIndex&idDashboard=2");
        }, done);
    });

    it("should load dashboard3 correctly", function (done) {
        expect.screenshot("dashboard3").to.be.captureSelector('.pageWrap,.expandDataTableFooterDrawer', function (page) {
            page.load("?" + urlBase + "#" + generalParams + "&module=Dashboard&action=embeddedIndex&idDashboard=3");
        }, done);
    });

    it("should load dashboard4 correctly", function (done) {
        expect.screenshot("dashboard4").to.be.captureSelector('.pageWrap,.expandDataTableFooterDrawer', function (page) {
            page.load("?" + urlBase + "#" + generalParams + "&module=Dashboard&action=embeddedIndex&idDashboard=4");
        }, done);
    });

    it("should display dashboard correctly on a mobile phone", function (done) {
        expect.screenshot("dashboard5_mobile").to.be.capture(function (page) { // capture with menu
            page.setViewportSize(480, 320);
            page.load("?" + urlBase + "#" + generalParams + "&module=Dashboard&action=embeddedIndex&idDashboard=5");
        }, done);
    });

    // visitors pages
    it('should load visitors > overview page correctly', function (done) {
        expect.screenshot("visitors_overview").to.be.captureSelector('.pageWrap,.expandDataTableFooterDrawer', function (page) {
            page.load("?" + urlBase + "#" + generalParams + "&module=VisitsSummary&action=index");
        }, done);
    });

    it('should load visitors > visitor log page correctly', function (done) {
        expect.screenshot("visitors_visitorlog").to.be.captureSelector('.pageWrap,.expandDataTableFooterDrawer', function (page) {
            page.load("?" + urlBase + "#" + generalParams + "&module=Live&action=indexVisitorLog");
        }, done);
    });

    it('should load the visitors > devices page correctly', function (done) {
        expect.screenshot("visitors_devices").to.be.captureSelector('.pageWrap,.expandDataTableFooterDrawer', function (page) {
            page.load("?" + urlBase + "#" + generalParams + "&module=DevicesDetection&action=index");
        }, done);
    });

    it('should load visitors > locations & provider page correctly', function (done) {
        expect.screenshot("visitors_locations_provider").to.be.captureSelector('.pageWrap,.expandDataTableFooterDrawer', function (page) {
            page.load("?" + urlBase + "#" + generalParams + "&module=UserCountry&action=index");
        }, done);
    });

    it('should load the visitors > software page correctly', function (done) {
        expect.screenshot("visitors_software").to.be.captureSelector('.pageWrap,.expandDataTableFooterDrawer', function (page) {
            page.load("?" + urlBase + "#" + generalParams + "&module=DevicesDetection&action=software");
        }, done);
    });

    it('should load the visitors > times page correctly', function (done) {
        expect.screenshot("visitors_times").to.be.captureSelector('.pageWrap,.expandDataTableFooterDrawer', function (page) {
            page.load("?" + urlBase + "#" + generalParams + "&module=VisitTime&action=index");
        }, done);
    });

    it('should load the visitors > engagement page correctly', function (done) {
        expect.screenshot("visitors_engagement").to.be.captureSelector('.pageWrap,.expandDataTableFooterDrawer', function (page) {
            page.load("?" + urlBase + "#" + generalParams + "&module=VisitFrequency&action=index");
        }, done);
    });

    it('should load the visitors > custom variables page correctly', function (done) {
        expect.screenshot('visitors_custom_vars').to.be.captureSelector('.pageWrap,.expandDataTableFooterDrawer', function (page) {
            page.load("?" + urlBase + "#" + generalParams + "&module=CustomVariables&action=menuGetCustomVariables");
        }, done);
    });

    it('should load the visitors > real-time map page correctly', function (done) {
        expect.screenshot('visitors_realtime_map').to.be.captureSelector('.pageWrap,.expandDataTableFooterDrawer', function (page) {
            page.load("?" + urlBase + "#" + idSite2Params + "&module=UserCountryMap&action=realtimeWorldMap"
                    + "&showDateTime=0&realtimeWindow=last2&changeVisitAlpha=0&enableAnimation=0&doNotRefreshVisits=1"
                    + "&removeOldVisits=0");
        }, done);
    });

    // actions pages
    it('should load the actions > pages page correctly', function (done) {
        expect.screenshot('actions_pages').to.be.captureSelector('.pageWrap,.expandDataTableFooterDrawer', function (page) {
            page.load("?" + urlBase + "#" + generalParams + "&module=Actions&action=menuGetPageUrls");
        }, done);
    });

    it('should load the actions > entry pages page correctly', function (done) {
        expect.screenshot('actions_entry_pages').to.be.captureSelector('.pageWrap,.expandDataTableFooterDrawer', function (page) {
            page.load("?" + urlBase + "#" + generalParams + "&module=Actions&action=menuGetEntryPageUrls");
        }, done);
    });

    it('should load the actions > exit pages page correctly', function (done) {
        expect.screenshot('actions_exit_pages').to.be.captureSelector('.pageWrap,.expandDataTableFooterDrawer', function (page) {
            page.load("?" + urlBase + "#" + generalParams + "&module=Actions&action=menuGetExitPageUrls");
        }, done);
    });

    it('should load the actions > page titles page correctly', function (done) {
        expect.screenshot('actions_page_titles').to.be.captureSelector('.pageWrap,.expandDataTableFooterDrawer', function (page) {
            page.load("?" + urlBase + "#" + generalParams + "&module=Actions&action=menuGetPageTitles");
        }, done);
    });

    it('should load the actions > site search page correctly', function (done) {
        expect.screenshot('actions_site_search').to.be.captureSelector('.pageWrap,.expandDataTableFooterDrawer', function (page) {
            page.load("?" + urlBase + "#" + generalParams + "&module=Actions&action=indexSiteSearch");
        }, done);
    });

    it('should load the actions > outlinks page correctly', function (done) {
        expect.screenshot('actions_outlinks').to.be.captureSelector('.pageWrap,.expandDataTableFooterDrawer', function (page) {
            page.load("?" + urlBase + "#" + generalParams + "&module=Actions&action=menuGetOutlinks");
        }, done);
    });

    it('should load the actions > downloads page correctly', function (done) {
        expect.screenshot('actions_downloads').to.be.captureSelector('.pageWrap,.expandDataTableFooterDrawer', function (page) {
            page.load("?" + urlBase + "#" + generalParams + "&module=Actions&action=menuGetDownloads");
        }, done);
    });

    it('should load the actions > contents page correctly', function (done) {
        expect.screenshot('actions_contents').to.be.captureSelector('.pageWrap,.expandDataTableFooterDrawer', function (page) {
            page.load("?" + urlBase + "#" + generalParams + "&module=Contents&action=index&period=day&date=2012-01-01");
        }, done);
    });

    it("should show all corresponding content pieces when clicking on a content name", function (done) {
        expect.screenshot("actions_content_name_piece").to.be.captureSelector('.pageWrap', function (page) {
            page.click('.dataTable .subDataTable .value:contains(ImageAd)');
        }, done);
    });

    it("should show all tracked content pieces when clicking on the table", function (done) {
        expect.screenshot("actions_content_piece").to.be.captureSelector('.pageWrap', function (page) {
            page.click('.reportDimension .dimension:contains(Content Piece)');
        }, done);
    });

    it("should show all corresponding content names when clicking on a content piece", function (done) {
        expect.screenshot("actions_content_piece_name").to.be.captureSelector('.pageWrap', function (page) {
            page.click('.dataTable .subDataTable .value:contains(Click NOW)');
        }, done);
    });

    // referrers pages
    it('should load the referrers > overview page correctly', function (done) {
        expect.screenshot('referrers_overview').to.be.captureSelector('.pageWrap,.expandDataTableFooterDrawer', function (page) {
            page.load("?" + urlBase + "#" + generalParams + "&module=Referrers&action=index");
        }, done);
    });

    // referrers pages
    it('should load the referrers > overview page correctly', function (done) {
        expect.screenshot('referrers_allreferrers').to.be.captureSelector('.pageWrap,.expandDataTableFooterDrawer', function (page) {
            page.load("?" + urlBase + "#" + generalParams + "&module=Referrers&action=allReferrers");
        }, done);
    });

    it('should load the referrers > search engines & keywords page correctly', function (done) {
        expect.screenshot('referrers_search_engines_keywords').to.be.captureSelector('.pageWrap,.expandDataTableFooterDrawer', function (page) {
            page.load("?" + urlBase + "#" + generalParams + "&module=Referrers&action=getSearchEnginesAndKeywords");
        }, done);
    });

    it('should load the referrers > websites & social page correctly', function (done) {
        expect.screenshot('referrers_websites_social').to.be.captureSelector('.pageWrap,.expandDataTableFooterDrawer', function (page) {
            page.load("?" + urlBase + "#" + generalParams + "&module=Referrers&action=indexWebsites");
        }, done);
    });

    it('should load the referrers > campaigns page correctly', function (done) {
        expect.screenshot('referrers_campaigns').to.be.captureSelector('.pageWrap,.expandDataTableFooterDrawer', function (page) {
            page.load("?" + urlBase + "#" + generalParams + "&module=Referrers&action=menuGetCampaigns");
        }, done);
    });

    // goals pages
    it('should load the goals > ecommerce page correctly', function (done) {
        expect.screenshot('goals_ecommerce').to.be.captureSelector('.pageWrap,.expandDataTableFooterDrawer', function (page) {
            page.load("?" + urlBase + "#" + generalParams + "&module=Ecommerce&action=ecommerceReport&idGoal=ecommerceOrder");
        }, done);
    });

    it('should load the goals > overview page correctly', function (done) {
        expect.screenshot('goals_overview').to.be.captureSelector('.pageWrap,.expandDataTableFooterDrawer', function (page) {
            page.load( "?" + urlBase + "#" + generalParams + "&module=Goals&action=index");
        }, done);
    });

    it('should load the goals > management page correctly', function (done) {
        expect.screenshot('goals_manage').to.be.captureSelector('.centerLargeDiv,.top_bar_sites_selector,.entityContainer', function (page) {
            page.load( "?" + generalParams + "&module=Goals&action=manage");
            page.wait(200);
        }, done);
    });

    it('should load the goals > single goal page correctly', function (done) {
        expect.screenshot('goals_individual_goal').to.be.captureSelector('.pageWrap,.expandDataTableFooterDrawer', function (page) {
            page.load("?" + urlBase + "#" + generalParams + "&module=Goals&action=goalReport&idGoal=1");
        }, done);
    });

    // one page w/ segment
    it('should load the visitors > overview page correctly when a segment is specified', function (done) {
        expect.screenshot('visitors_overview_segment').to.be.captureSelector('.pageWrap,.expandDataTableFooterDrawer', function (page) {
            page.load("?" + urlBase + "#" + generalParams + "&module=VisitsSummary&action=index&segment=" + segment);
        }, done);
    });

    // example ui pages
    it('should load the example ui > dataTables page correctly', function (done) {
        expect.screenshot('exampleui_dataTables').to.be.captureSelector('.pageWrap,.expandDataTableFooterDrawer', function (page) {
            page.load("?" + urlBase + "#" + generalParams + "&module=ExampleUI&action=dataTables");
        }, done);
    });

    it('should load the example ui > barGraph page correctly', function (done) {
        expect.screenshot('exampleui_barGraph').to.be.captureSelector('.pageWrap,.expandDataTableFooterDrawer', function (page) {
            page.load("?" + urlBase + "#" + generalParams + "&module=ExampleUI&action=barGraph");
        }, done);
    });

    it('should load the example ui > pieGraph page correctly', function (done) {
        expect.screenshot('exampleui_pieGraph').to.be.captureSelector('.pageWrap,.expandDataTableFooterDrawer', function (page) {
            page.load("?" + urlBase + "#" + generalParams + "&module=ExampleUI&action=pieGraph");
        }, done);
    });

    it('should load the example ui > tagClouds page correctly', function (done) {
        expect.screenshot('exampleui_tagClouds').to.be.captureSelector('.pageWrap,.expandDataTableFooterDrawer', function (page) {
            page.load("?" + urlBase + "#" + generalParams + "&module=ExampleUI&action=tagClouds");
        }, done);
    });

    it('should load the example ui > sparklines page correctly', function (done) {
        expect.screenshot('exampleui_sparklines').to.be.captureSelector('.pageWrap,.expandDataTableFooterDrawer', function (page) {
            page.load("?" + urlBase + "#" + generalParams + "&module=ExampleUI&action=sparklines");
        }, done);
    });

    it('should load the example ui > evolution graph page correctly', function (done) {
        expect.screenshot('exampleui_evolutionGraph').to.be.captureSelector('.pageWrap,.expandDataTableFooterDrawer', function (page) {
            page.load("?" + urlBase + "#" + generalParams + "&module=ExampleUI&action=evolutionGraph");
        }, done);
    });

    it('should load the example ui > treemap page correctly', function (done) {
        expect.screenshot('exampleui_treemap').to.be.captureSelector('.pageWrap,.expandDataTableFooterDrawer', function (page) {
            page.load("?" + urlBase + "#" + generalParams + "&module=ExampleUI&action=treemap");
            page.wait(2000);
        }, done);
    });

    // widgetize
    it('should load the widgetized visitor log correctly', function (done) {
        expect.screenshot('widgetize_visitor_log').to.be.capture(function (page) {
            page.load("?" + widgetizeParams + "&" + generalParams + "&moduleToWidgetize=Live&actionToWidgetize=getVisitorLog");
            page.evaluate(function () {
                $('.expandDataTableFooterDrawer').click();
            });
        }, done);
    });

    it('should load the widgetized all websites dashboard correctly', function (done) {
        expect.screenshot('widgetize_allwebsites').to.be.capture(function (page) {
            page.load("?" + widgetizeParams + "&" + generalParams + "&moduleToWidgetize=MultiSites&actionToWidgetize=standalone");
        }, done);
    });

    it('should widgetize the ecommerce log correctly', function (done) {
        expect.screenshot('widgetize_ecommercelog').to.be.capture(function (page) {
            page.load("?" + widgetizeParams + "&" + generalParams + "&moduleToWidgetize=Ecommerce&actionToWidgetize=getEcommerceLog&filter_limit=-1");
        }, done);
    });

    // Do not allow API response to be displayed
    it('should not allow to widgetize an API call', function (done) {
        expect.screenshot('widgetize_apidisallowed').to.be.capture(function (page) {
            page.load("?" + widgetizeParams + "&" + generalParams + "&moduleToWidgetize=API&actionToWidgetize=index&method=SitesManager.getImageTrackingCode&piwikUrl=test");
        }, done);
    });

    it('should not display API response in the content', function (done) {
        expect.screenshot('menu_apidisallowed').to.be.captureSelector('#content', function (page) {
            page.load("?" + urlBase + "#" + generalParams + "&module=API&action=SitesManager.getImageTrackingCode");
        }, done);
    });

    // Ecommerce
    it('should load the ecommerce overview page', function (done) {
        expect.screenshot('ecommerce_overview').to.be.captureSelector('.pageWrap,.expandDataTableFooterDrawer', function (page) {
            page.load("?" + urlBase + "#" + generalParams + "&module=Ecommerce&action=ecommerceReport&idGoal=ecommerceOrder");
        }, done);
    });

    it('should load the ecommerce log page', function (done) {
        expect.screenshot('ecommerce_log').to.be.captureSelector('.pageWrap,.expandDataTableFooterDrawer', function (page) {
            page.load("?" + urlBase + "#" + generalParams + "&module=Ecommerce&action=ecommerceLogReport");
        }, done);
    });

    it('should load the ecommerce products page', function (done) {
        expect.screenshot('ecommerce_products').to.be.captureSelector('.pageWrap,.expandDataTableFooterDrawer', function (page) {
            page.load("?" + urlBase + "#" + generalParams + "&module=Ecommerce&action=products&idGoal=ecommerceOrder");
        }, done);
    });

    it('should load the ecommerce sales page', function (done) {
        expect.screenshot('ecommerce_sales').to.be.captureSelector('.pageWrap,.expandDataTableFooterDrawer', function (page) {
            page.load("?" + urlBase + "#" + generalParams + "&module=Ecommerce&action=sales&idGoal=ecommerceOrder");
        }, done);
    });

    // Admin user settings (plugins not displayed)
    it('should load the Manage > Websites admin page correctly', function (done) {
        expect.screenshot('admin_manage_websites').to.be.captureSelector('#content', function (page) {
            page.load("?" + generalParams + "&module=SitesManager&action=index");
            page.evaluate(function () {
                $('.form-help:contains(UTC time is)').hide();
            });
        }, done);
    });

    it('should load the Manage > Users admin page correctly', function (done) {
        expect.screenshot('admin_manage_users').to.be.captureSelector('#content', function (page) {
            page.load("?" + generalParams + "&module=UsersManager&action=index");

            // remove token auth which can be random
            page.evaluate(function () {
                $('td#token_auth').each(function () {
                    $(this).text('');
                });
                $('td#last_seen').each(function () {
                    $(this).text( '' )
                });
            });
        }, done);
    });

    it('should load the user settings admin page correctly', function (done) {
        expect.screenshot('admin_user_settings').to.be.captureSelector('#content', function (page) {
            page.load("?" + generalParams + "&module=UsersManager&action=userSettings");
        }, done);
    });

    it('should load the Manage > Tracking Code admin page correctly', function (done) {
        expect.screenshot('admin_manage_tracking_code').to.be.captureSelector('#content', function (page) {
            page.load("?" + generalParams + "&module=CoreAdminHome&action=trackingCodeGenerator");
        }, done);
    });

    it('should load the Settings > General Settings admin page correctly', function (done) {
        expect.screenshot('admin_settings_general').to.be.captureSelector('#content', function (page) {
            page.load("?" + generalParams + "&module=CoreAdminHome&action=generalSettings");
        }, done);
    });

    it('should load the Settings > Privacy admin page correctly', function (done) {
        expect.screenshot('admin_privacy_settings').to.be.captureSelector('#content,.ui-inline-help', function (page) {
            page.load("?" + generalParams + "&module=PrivacyManager&action=privacySettings");
        }, done);
    });

    it('should load the Privacy Opt out iframe correctly', function (done) {
        expect.screenshot('admin_privacy_optout_iframe').to.be.capture(function (page) {
            page.load("?module=CoreAdminHome&action=optOut&language=de");
        }, done);
    });

    it('should load the Settings > Mobile Messaging admin page correctly', function (done) {
        expect.screenshot('admin_settings_mobilemessaging').to.be.captureSelector('#content', function (page) {
            page.load("?" + generalParams + "&module=MobileMessaging&action=index");
        }, done);
    });

    it('should load the Settings > Mobile Messaging user page correctly', function (done) {
        expect.screenshot('user_settings_mobilemessaging').to.be.captureSelector('#content', function (page) {
            page.load("?" + generalParams + "&module=MobileMessaging&action=userSettings");
        }, done);
    });

    it('should load the themes admin page correctly', function (done) {
        expect.screenshot('admin_themes').to.be.captureSelector('#content', function (page) {
            page.load("?" + generalParams + "&module=CorePluginsAdmin&action=themes");
        }, done);
    });

    it('should load the plugins admin page correctly', function (done) {
        expect.screenshot('admin_plugins').to.be.captureSelector('#content', function (page) {
            page.load("?" + generalParams + "&module=CorePluginsAdmin&action=plugins");
        }, done);
    });

    it('should load the plugin settings admin page correctly', function (done) {
        expect.screenshot('admin_plugin_settings').to.be.captureSelector('#content', function (page) {
            page.load("?" + generalParams + "&module=CoreAdminHome&action=adminPluginSettings");
        }, done);
    });

    it('should load the plugin settings user page correctly', function (done) {
        expect.screenshot('user_plugin_settings').to.be.captureSelector('#content', function (page) {
            page.load("?" + generalParams + "&module=CoreAdminHome&action=userPluginSettings");
        }, done);
    });

    it('should load the Settings > Visitor Generator admin page correctly', function (done) {
        expect.screenshot('admin_visitor_generator').to.be.captureSelector('#content', function (page) {
            page.load("?" + generalParams + "&module=VisitorGenerator&action=index");

            page.evaluate(function () {
                var $p = $('#content p:eq(1)');
                $p.text($p.text().replace(/\(change .*\)/g, ''));
            });
        }, done);
    });

    // Notifications
    it('should load the notifications page correctly', function (done) {
        expect.screenshot('notifications').to.be.capture(function (page) {
            page.load("?" + generalParams + "&module=ExampleUI&action=notifications&idSite=1&period=day&date=yesterday");
            page.evaluate(function () {
                $('#header').hide();
            });
        }, done);
    });

    // Fatal error safemode
    it('should load the safemode fatal error page correctly', function (done) {
        var message = "Call%20to%20undefined%20function%20Piwik%5CPlugins%5CFoobar%5CPiwik_Translate()",
            file = "%2Fhome%2Fvagrant%2Fwww%2Fpiwik%2Fplugins%2FFoobar%2FFoobar.php%20line%205",
            line = 58;

        expect.screenshot('fatal_error_safemode').to.be.capture(function (page) {
            page.load("?" + generalParams + "&module=CorePluginsAdmin&action=safemode&idSite=1&period=day&date=yesterday&activated"
                    + "&error_message=" + message + "&error_file=" + file + "&error_line=" + line + "&tests_hide_piwik_version=1");
        }, done);
    });

    // DB error message
    it('should fail correctly when db information in config is incorrect', function (done) {
        testEnvironment.configOverride = {
            database: {
                host: '127.50.50.50',
                username: 'slkdfjsdlkfj',
                password: 'slkdfjsldkfj',
                dbname: 'abcdefg',
                tables_prefix: 'gfedcba'
            }
        };
        testEnvironment.save();

        expect.screenshot('db_connect_error').to.be.capture(function (page) {
            page.load("");
        }, done);
    });

    // CustomAlerts plugin TODO: move to CustomAlerts plugin
    it('should load the custom alerts list correctly', function (done) {
        expect.screenshot('customalerts_list').to.be.capture(function (page) {
            page.load("?" + generalParams + "&module=CustomAlerts&action=index&idSite=1&period=day&date=yesterday&tests_hide_piwik_version=1");
        }, done);
    });

    it('should load the triggered custom alerts list correctly', function (done) {
        expect.screenshot('customalerts_list_triggered').to.be.capture(function (page) {
            page.load("?" + generalParams + "&module=CustomAlerts&action=historyTriggeredAlerts&idSite=1&period=day&date=yesterday&tests_hide_piwik_version=1");
        }, done);
    });

    // top bar pages
    it('should load the widgets listing page correctly', function (done) {
        expect.screenshot('widgets_listing').to.be.captureSelector('#content', function (page) {
            page.load("?" + generalParams + "&module=Widgetize&action=index");
            page.mouseMove('.widgetpreview-categorylist>li:contains(Visits Summary)');
            page.mouseMove('li[uniqueid=widgetVisitsSummarygetEvolutionGraphcolumnsArray]');
        }, done);
    });

    it('should load the API listing page correctly', function (done) {
        expect.screenshot('api_listing').to.be.captureSelector('#content', function (page) {
            page.load("?" + generalParams + "&module=API&action=listAllAPI");
            page.evaluate(function () { // remove token_auth since it can change on each test run
                $('span#token_auth>strong').text('dummytokenauth');
            });
        }, done);
    });

    it('should load the email reports page correctly', function (done) {
        expect.screenshot('email_reports').to.be.capture(function (page) {
            page.load("?" + generalParams + "&module=ScheduledReports&action=index");
            page.evaluate(function () {
                $('#header').hide();
            });
        }, done);
    });

    it('should load the feedback form when the feedback form link is clicked', function (done) {
        expect.screenshot('feedback_form').to.be.capture(function (page) {

            page.load("?" + generalParams + "&module=Feedback&action=index");

            page.evaluate(function () {
                $('h2 span').each(function () {
                    if ($(this).text().indexOf("Piwik") !== -1) {
                        var replace = $(this).text().replace(/Piwik\s*\d+\.\d+(\.\d+)?([\-a-z]*\d+)?/g, 'Piwik');
                        $(this).text(replace);
                    }
                });

                $('#header').hide();
            });
        }, done);
    });

    // date range clicked
    it('should reload to the correct date when a date range is selected in the period selector', function (done) {
        expect.screenshot('period_select_date_range_click').to.be.capture(function (page) {
            page.load("?" + urlBase + "#" + generalParams + "&module=VisitTime&action=index");
            page.evaluate(function () {
                $(document).ready(function () {
                    $('#date').click();
                    $('#period_id_range').click();
                    $('#inputCalendarFrom').val('2012-08-02');
                    $('#inputCalendarTo').val('2012-08-12');
                    setTimeout(function () {$('#calendarRangeApply').click();}, 500);
                });
            });
        }, done);
    });

    // visitor profile popup
    it('should load the visitor profile popup correctly', function (done) {
        expect.screenshot('visitor_profile_popup').to.be.capture(function (page) {
            page.load("?" + widgetizeParams + "&" + idSite2Params + "&moduleToWidgetize=Live&actionToWidgetize=getVisitorProfilePopup"
                    + "&enableAnimation=0");

            page.evaluate(function () {
                $(document).ready(function () {
                    $('.visitor-profile-show-map').click();
                });
            });

            page.wait(1000);
        }, done);
    });

    // opt out page
    it('should load the opt out page correctly', function (done) {
        expect.screenshot('opt_out').to.be.capture(function (page) {
            testEnvironment.testUseMockAuth = 0;
            testEnvironment.save();

            page.load("?module=CoreAdminHome&action=optOut&language=en");
        }, done);
    });


});