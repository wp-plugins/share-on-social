/*
 * show and process lockers on ready
 */
jQuery(document).ready(function() {

    showFBShares();
    showGPlusShares();
    showTwitterShares();

    processLockers();
});

/*
 * try to unlock content for each shareName
 */
function processLockers() {
    var shareNames = getShareNames();
    for ( var i = 0; i < shareNames.length; i++) {
	var shareName = shareNames[i].value;
	unlockContent(shareName);
    }
}

/*
 * show facebook share buttons
 */
function showFBShares() {
    /*
     * get array of shareNames and for each button with shareName register
     * onclick function.
     */
    var shareNames = getShareNames();
    for ( var i = 0; i < shareNames.length; i++) {
	var shareName = shareNames[i].value;
	var shareSpans = getShareSpans(shareName, 'fb');
	for ( var j = 0; j < shareSpans.length; j++) {
	    var shareSpan = shareSpans[j];
	    shareSpan.onclick = shareWithFB;
	}
    }
}

/*
 * show GPlus share buttons
 */
function showGPlusShares() {
    var shareNames = getShareNames();
    for ( var i = 0; i < shareNames.length; i++) {
	var shareName = shareNames[i].value;
	var shareSpans = getShareSpans(shareName, 'gplus');
	console.log(shareName);
	console.log(shareSpans);
	for ( var j = 0; j < shareSpans.length; j++) {
	    var shareSpan = shareSpans[j];
	    var prefix = getPrefix(shareSpan);
	    var shareTarget = getShareTarget(shareName,prefix);
	    renderGPlusShare(shareSpan, shareName, shareTarget);
	}
    }
}

/*
 * show twitter share buttons
 */
function showTwitterShares() {

    var shareNames = getShareNames();
    for ( var i = 0; i < shareNames.length; i++) {
	var shareName = shareNames[i].value;
	var shareSpans = getShareSpans(shareName, 'twitter');
	console.log(shareName);
	console.log(shareSpans);
	for ( var j = 0; j < shareSpans.length; j++) {
	    var shareSpan = shareSpans[j];
	    var prefix = getPrefix(shareSpan);
	    var shareTarget = getShareTarget(shareName,prefix);
	    renderTwitterButton(shareSpan, shareName, shareTarget);
	}
    }

}

/*
 * render twitter button
 * 
 * @param string shareSpan - span to show the button @param string shareName -
 * share name @param string shareTarget - share link
 */
function renderTwitterButton(shareSpan, shareName, shareTarget) {
    var twitterButtonId = null;
    twttr.ready(function(twttr) {
	twttr.widgets.createShareButton(shareTarget, shareSpan, {
	    size : 'large',
	    count : 'none',
	    text : 'Sharing a URL using the Tweet Button'
	}).then(function(el) {
	    // save the twitter iframe id
	    twitterButtonId = el.id;
	});
	twttr.events.bind('tweet', function(intentEvent) {
	    if (!intentEvent)
		return;
	    // twitter iframe id == intent target id only for button clicked
	    // we use this behaviour to get the shareSpan id
	    if (intentEvent.target.id == twitterButtonId) {
		handleTwitterResponse(shareName, intentEvent);
	    }
	});
    });
}

/*
 * render facebook button
 * 
 * @param string shareSpan - span to show the button @param string shareName -
 * share name @param string shareTarget - share link
 */
function shareWithFB() {
    /*
     * on button click, get its id which is also the shareName use the shareName
     * to get shareTarget and call FB
     */

    // extract shareName from span id - fb_{$prefix}-shareName
    var shareName = getShareName(this);
    var prefix = getPrefix(this);
    var shareTarget = getShareTarget(shareName,prefix);

    FB.login(function(response) {
	if (response.authResponse) {
	    FB.ui({
		method : 'share_open_graph',
		action_type : 'og.likes',
		display : 'popup',
		action_properties : JSON.stringify({
		    object : shareTarget,
		})
	    }, function(response) {
		handleFbResponse(shareName, response);
	    });
	} else {
	    console.log('User cancelled login or authorization.');
	}
    }, {
	scope : 'publish_actions'
    });
}

/*
 * render gplus button
 * 
 * @param string shareSpan - span to show the button @param string shareName -
 * share name @param string shareTarget - share link
 */
function renderGPlusShare(shareSpan, shareName, shareTarget) {
    var shareOptions = getGPlusShareOptions(shareName, shareTarget);
    // render the share defined by shareOption in element gplus- + shareName
    gapi.interactivepost.render(shareSpan.id, shareOptions);
}

/*
 * construct Google Plus Share options
 */
function getGPlusShareOptions(shareName, shareTarget) {
    var shareOptions = {
	contenturl : shareTarget,
	clientid : sos_data.gplus_client_id,
	cookiepolicy : 'single_host_origin',
	calltoactionlabel : 'DISCOVER',
	calltoactionurl : shareTarget,
	onshare : function(response) {
	    handleGPlusResponse(shareName, response);
	}
    };
    return shareOptions;
}

/*
 * handle twitter response
 */
function handleTwitterResponse(shareName, intentEvent) {
    var response = {};
    handleResponse('twitter', shareName, response);
}

/*
 * handle gplus response
 */
function handleGPlusResponse(shareName, response) {
    if (response && 'completed' == response.status) {
	if ('shared' == response.action) {
	    console.log(shareName + " " + JSON.stringify(response));
	    handleResponse('gplus', shareName, response);
	}
    }
}

/*
 * handle fb response
 */
function handleFbResponse(shareName, response) {
    console.log("og.likes create : " + JSON.stringify(response));
    if (!response) {
	console.log('unknown error and response : ' + response);
    } else {
	if (response.error_code) {
	    console.log('error occured : ' + response);
	    if ('3501' == response.error_code) {
		openLocker(shareName);
	    }
	} else {
	    FB.api("/me/og.likes", function(response) {
		console.log(JSON.stringify(response));
		handleResponse('fb', shareName, response);
	    });
	}
    }
}

/*
 * send stats by ajax call
 */
function handleResponse(type, shareName, response) {

    jQuery.ajax({
	type : 'POST',
	url : sos_data.ajax_url,
	data : {
	    action : 'save-stats',
	    type : type,
	    share_name : shareName,
	    share_stats : JSON.stringify(response),
	    _ajax_nonce : sos_data.nonce,
	},
	success : function(data, textStatus, XMLHttpRequest) {
	    console.log(data);
	    openLocker(shareName);
	},
	error : function(MLHttpRequest, textStatus, errorThrown) {
	    console.log(errorThrown);
	}
    });
}

/*
 * get list of share names
 */
function getShareNames() {
    // for comparison, hold unique share names in separate array
    var names = [];
    // collect unique share names elements in another array
    var shareNames = [];
    jQuery('.share_name').each(function() {
	if (jQuery.inArray(jQuery(this).prop('value'), names) === -1) {
	    names.push(jQuery(this).prop('value'));
	    shareNames.push(this);
	}
    });
    return shareNames;
}

/*
 * get share name of an element
 */
function getShareName(element) {
    if (undefined == element.id || '' == element.id) {
	return undefined;
    }
    var index = (element.id).indexOf('-');
    if (-1 == index) {
	return undefined;
    }
    index++; // ignore the dash
    var shareName = (element.id).substring(index);
    return shareName;
}

/*
 * get share name of an element
 */
function getPrefix(element) {
    if (undefined == element.id || '' == element.id) {
	return undefined;
    }
    var index = (element.id).indexOf('_');
    if (-1 == index) {
	return undefined;
    }
    index++; // ignore the underscore
    var endIndex = (element.id).indexOf('-');
    if (-1 == endIndex) {
	return undefined;
    }
    var prefix = (element.id).substring(index, endIndex);
    console.log('prefix ' + prefix);
    return prefix;
}

/*
 * get share target (link) for a sharename
 */
function getShareTarget(shareName, prefix) {
    var shareTarget = jQuery('#' + shareName + '_' + prefix + '_share_target')
	    .attr('value');
    return shareTarget;
}

/*
 * get list of share spans for a share name and share type (fb,gplus..)
 */
function getShareSpans(shareName, shareType) {
    // shareType - fb, gplus, twitter etc.
    var shareSpans = jQuery('.' + shareType + '-' + shareName);
    return shareSpans;
}

/*
 * set cookie and unlock locker
 */
function openLocker(shareName) {
    createCookie(shareName, true, 8760); // expiry 365 days
    if (isCookieSet(shareName)) {
	unlockContent(shareName);
    }
}

/*
 * toggle class (style) to show content and hide locker
 */
function unlockContent(shareName) {
    // if cookie exists then show contents and hide locker
    if (isCookieSet(shareName)) {
	jQuery('.' + shareName + '-sos-content').removeClass('sos-hide');
	jQuery('.' + shareName + '-sos-locker').addClass('sos-hide');
    }
}

/*
 * set cookie
 */
function createCookie(name, value, hours) {
    cookiePrefix = 'sos-';
    cookieName = cookiePrefix + name;
    var expires;
    if (hours > 0) {
	var date = new Date();
	date.setTime(date.getTime() + (hours * 60 * 60 * 1000));
	expires = "; expires=" + date.toGMTString();
    } else {
	expires = "";
    }
    document.cookie = escape(cookieName) + "=" + escape(value) + "; " + expires
	    + "; path=/";
    return date;
}

/*
 * check whether cookie is set
 */
function isCookieSet(name) {
    cookiePrefix = 'sos-';
    cookieName = cookiePrefix + name;
    console.log(document.cookie);
    cookieSetPattern = cookieName + '=true';
    if (document.cookie.indexOf(cookieSetPattern) >= 0) {
	return true;
    } else {
	return false;
    }
}
