/*!
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

/*global Piwik getToken */

Piwik.addPlugin('testPlugin', {
	/*
	 * called when tracker instantiated
	 * - function or string to be eval()'d
	 */
	run: function (registerHookCallback) {
		registerHookCallback('test',
			'{' +
				'_isDefined : isDefined,' +
				'_isFunction : isFunction,' +
				'_isObject : isObject,' +
				'_isString : isString,' +
				'_isSiteHostName : isSiteHostName,' +
				'_getClassesRegExp : getClassesRegExp,' +
				'_hasCookies : hasCookies,' +
				'_getCookie : getCookie,' +
				'_getCookieName : getCookieName,' +
				'_setCookie : setCookie,' +
				'_encode : encodeWrapper,' +
				'_decode : decodeWrapper,' +
				'_urldecode : urldecode,' +
				'_getLinkType : getLinkType,' +
				'_beforeUnloadHandler : beforeUnloadHandler,' +
				'_getProtocolScheme : getProtocolScheme,' +
				'_getHostName : getHostName,' +
				'_getParameter : getParameter,' +
				'_urlFixup : urlFixup,' +
				'_domainFixup : domainFixup,' +
				'_titleFixup : titleFixup,' +
				'_sha1 : sha1,' +
				'_utf8_encode : utf8_encode,' +
				'_purify : purify,' +
				'_resolveRelativeReference : resolveRelativeReference,' +
				'_addEventListener : addEventListener,' +
				'_prefixPropertyName : prefixPropertyName' +
			'}'
		);
	},

	/*
	 * called when DOM ready
	 */
	load: function () { },

	/*
	 * function called on trackPageView
	 * - returns URL components to be appended to tracker URL
	 */
	log: function () {
		return '&testlog=' + encodeURIComponent('{"token":"' + getToken() + '"}');
	},

	/*
	 * function called on trackLink() or click event
	 * - returns URL components to be appended to tracker URL
	 */
	link: function () {
		return '&testlink=' + encodeURIComponent('{"token":"' + getToken() + '"}');
	},

	/*
	 * function called on trackGoal()
	 * - returns URL components to be appended to tracker URL
	 */
	goal: function () {
		return '&testgoal=' + encodeURIComponent('{"token":"' + getToken() + '"}');
	},

	/*
	 * called before page is unloaded
	 */
	unload: function () { }
});
