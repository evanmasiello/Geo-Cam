var imageDataUrl = null;
var jsonArray;
var imageHTMl = "";
var imageArray = [];
var imagesToDisplay = [];
var likedPosts = "nill";
var timeMin = 0;
var blockedCookie = getCookie("blocked");
if (blockedCookie === "") blockedCookie = "[]";
var postMode = "norm";
var deletedPosts = [];
var profileFilter = -1;

var blockedUsers = [];
var followedUsers = [];

var openComments = -1;

console.log("blocked length: " + blockedUsers.length);

function userIsFollowed(id) {
    
    for (var c=0; c < followedUsers.length; c++) {
        if (followedUsers[c].id == id) {
            return true;
            // showProfileUserName = followedUsers[c].name;
        }
    }
    
    return false;
    
}

console.log("is user blocked: " + userIsBlocked(2));

function userIsBlocked(id) {
    
    for (var c=0; c < blockedUsers.length; c++) {
        if (blockedUsers[c].id == id) {
            return true;
            // showProfileUserName = blockedUsers[c].name;
        }
    }
    
    return false;
    
}

function setPadderHeight() {
    var clientHeight = document.getElementById('content').clientHeight;

    var clientHeightHead = document.getElementById('header').clientHeight;

    let height = window.innerHeight;
    
    var heightVar = height - clientHeight;// - clientHeightHead;
    
    if (heightVar < 0) {
        heightVar = 0;
    }
    
    document.getElementById("offsetDiv").style.height = heightVar + 10 + "px";
}

setPadderHeight();

/*

var blockedUsers = [];

for (var x=0; x < blockedUsersTemp.length; x++) {
    blockedUsers.push(blockedUsersTemp[x]);
}

*/

// JavaScript program to calculate Distance Between
// Two Points on Earth

//console.log("json data " + JSON.parse(getCookie("likedPosts")));

//navigator.geolocation.getCurrentPosition(successInit);

//var cookieSesh = getCookie("session");

//console.log("cookie Post: " + cookiePost);

var session = getCookie("session");

if (session !== "") {
//if (false) {
    //setTimeout(getUserLikes(session), 1000);
    //sign in and get likes
    //session = cookieSesh;
} else {
    
    likedPosts = [];
    session = "";
    
    var x=0;
    
    //showPostsInit();
    
    //setTimeout(showPostsInit, 1000);
    
}

function showPostsInit() { /*
    while (window.innerHeight > (window.innerWidth * 3 * x)) {
        fillPics(imageArray); x++;
    }  */
}

function logOut() {
    session = "";
    
    setCookie("session", session, 1);
    
    likedPosts = [];
    
    updateUserLikesDisplay();
    
    accessCode = "";
    
    //set all like buttons to not logged in state
}

//USE SESSION TO GET LIKES

function printSession(thing) {
   //console.log("print this " + thing);
}

function getUserLikes() {
    var formDataLikes = new FormData();
    formDataLikes.append("session", session);

    var xhttpLikes = new XMLHttpRequest();

    // Set POST method and ajax file path
    xhttpLikes.open("POST", "./php/getUserLikes.php?_='" + Date.now(), true);
    
    // call on request changes state
    xhttpLikes.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {

            var responseText = this.responseText;
            
         //console.log("getUser gotten");
            
         //console.log("get user likes response: " + responseText);
            
            //setCookie("codeUser", response, 999999);

            likedPosts = JSON.parse(responseText);
            
            updateUserLikesDisplay();
            
            // while (window.innerHeight > document.body.clientHeight) {
            //     if (imageArray.length > 0) fillPics(imagesToDisplay);
            // }
            
        }
    };

    xhttpLikes.send(formDataLikes);
}

function error() {
   //console.log("Can't get location!");
    document.getElementById("feed").innerHTML = "<h2 class='contentText'>Uh oh! We can't tell where you are. Please allow us to access your location and reload.</h2>";
}

function removeNull(array) {
    return array.filter(x => x !== null);
}

function letShowAllPosts() {
    document.getElementById("switchPostMode").style.display = "block";
}

function togglePostMode() {
    
   //console.log("toggle Post Mode");
    
    if (postMode == "norm") {
        postMode = "admin";
        imageArray = [];
        imagesToDisplay = [];
        showPosts();
    } else {
        postMode = "norm";
        imageArray = [];
        imagesToDisplay = [];
        showPosts(); 
    }
    
    //updateUserLikesDisplay();
}

var slider = document.getElementById("mileRange");
var output = document.getElementById("milesDisplay");
output.innerHTML = slider.value/5280 + " mile";

slider.oninput = function() {
  var input = this.value/5280;
  
  /*
  var arrayOfPosts = [];
  
  for (var i=0; i < imageArray.length; i++) {
      if (imageArray[i].distance < input) {
          //console.log("Pushing Posth #" + imageArray[i].id);
          arrayOfPosts.push(imageArray[i]);
      }
  }
  */
  
  if (input < 0.5) {
    input = input * 5280;
    if (input == 1) {
      output.innerHTML = Math.round(input) + " foot";
    } else {
      output.innerHTML = Math.round(input) + " feet";
    }
  } else {
    if (input == 1) {
      output.innerHTML = input.toFixed(2) + " mile";
    } else {
      output.innerHTML = input.toFixed(2) + " miles";   
    }
  }
  
  //display(arrayOfPosts);
  filterPosts();
}

var postsToDisplay = "all";

var arrayOfPosts = [];

var usersBlockedMe = [];
var usersFollowingMe = [];

function filterPosts() {
  arrayOfPosts = [];
  
  var dateObjMax = document.getElementById("dateEnd");
  var dateObjMin = document.getElementById("dateStart");
  var distanceSlider = document.getElementById("mileRange");
  
  var distanceInput = distanceSlider.value / 5280;
  var timeMax = (dateObjMax.valueAsDate.getTime() / 1000) + 86400;
  var timeMin = dateObjMin.valueAsDate.getTime() / 1000;
  
  var postsToShow = document.getElementById("sortFilt").value;
    
 //console.log("posts to show is: " + postsToShow);
   //console.log("distanceInput is: " + distanceInput);
  
//   followLiked = [];
  
//   for (var x=0; x < allUsers.length; x++) {
//       if (followedUsers.includes(allUsers[x].id)) {
//           var followingLiked = JSON.parse(allUsers[x].likes);
//           for (var c=0; c < followingLiked.length; c++) {
//               followLiked.push(followingLiked[c]);
//           }
//       }
//   }
  
//   usersBlockedMe = [];
//   usersFollowingMe = [];
  
//   for (var p=0; p < allUsers.length; p++) {
//       if (allUsers[p].blocked !== undefined) {
//         var userBlocked = JSON.parse(allUsers[p].blocked);
//         if (userBlocked.includes(userId)) {
//             usersBlockedMe.push(allUsers[p].id);
//         }
//       }
//       if (allUsers[p].followed !== undefined) {
//        //console.log("&& in user if");
//         var userFollowing = JSON.parse(allUsers[p].followed);
//         if (userFollowing.includes(userId)) {
//             usersFollowingMe.push(allUsers[p].id);
//            //console.log("a user follows you");
//         }
//       }
//   }
  
 //console.log("the amount of users that follow you: " + usersFollowingMe);
    
  //console.log("distance input: " + distanceInput);
  //console.log("time max: " + timeMax);
  //console.log("time max: " + timeMin);
  
  //var shouldShow = (postsToShow == "alll" || (postsToShow == "liked" && likedPosts.includes(imageArray[i].id) || (postsToShow == "posted" && imageArray[i].user == userId)));
    
  for (var i=0; i < imageArray.length; i++) {
      
   //console.log("post distance: " + imageArray[i].distance);
       //console.log("post id: " + imageArray[i].id);
       //console.log("distance input is: " + distanceInput);
    //console.log("post time: " + imageArray[i].time);
    
    //imageArray[i].distance < distanceInput && 
    
    if (imageArray[i].distance < distanceInput && imageArray[i].time < timeMax && imageArray[i].time > timeMin && !userIsBlocked(imageArray[i].user) && (postsToShow == "all" || (postsToShow == "liked" && likedPosts.includes(imageArray[i].id) || (postsToShow == "posted" && imageArray[i].user == userId) || (postsToShow == "followposted" && userIsFollowed(imageArray[i].user)) || (postsToShow == "followliked" && followLiked.includes(imageArray[i].id)))) && (profileFilter == -1 || imageArray[i].user == profileFilter) && !usersBlockedMe.includes(imageArray[i].user) && (imageArray[i].visibility != "following" || usersFollowingMe.includes(imageArray[i].user) || imageArray[i].user == userId)) {
     //console.log("Pushing Post #" + imageArray[i].id);
      arrayOfPosts.push(imageArray[i]);
    }
    
    if (postMode == "admin") {
        if (imageArray[i].time < timeMax && imageArray[i].time > timeMin && !userIsBlocked(imageArray[i].user) && (postsToShow == "all" || (postsToShow == "liked" && likedPosts.includes(imageArray[i].id)) || (postsToShow == "posted" && imageArray[i].user == userId) || (postsToShow == "followposted" && userIsFollowed(imageArray[i].user)) || (postsToShow == "followliked" && followLiked.includes(imageArray[i].id))) && (profileFilter == -1 || imageArray[i].user == profileFilter)) {
        //console.log("Pushing Post #" + imageArray[i].id);
            arrayOfPosts.push(imageArray[i]);
        }
    }
    
  }
  
  display(arrayOfPosts);
}

function distance(lat1, lat2, lon1, lon2) {
    //console.log("lat1: " + lat1 + ", lat2: " + lat2 + ", lon1: " + lon1 + ", lon2: " + lon2);
    // The math module contains a function
    // named toRadians which converts from
    // degrees to radians.
    lon1 = lon1 * Math.PI / 180;
    lon2 = lon2 * Math.PI / 180;
    lat1 = lat1 * Math.PI / 180;
    lat2 = lat2 * Math.PI / 180;

    // Haversine formula
    let dlon = lon2 - lon1;
    let dlat = lat2 - lat1;
    let a = Math.pow(Math.sin(dlat / 2), 2) +
        Math.cos(lat1) * Math.cos(lat2) *
        Math.pow(Math.sin(dlon / 2), 2);

    let c = 2 * Math.asin(Math.sqrt(a));

    // 6371 = Radius of earth in kilometers. Use 3956
    // for miles
    let r = 3956;

    //console.log("c: " + c);
    //console.log("r: " + r);
    // calculate the result
    return (c * r);
}

function sortImages() {
    var selectForm = document.getElementById("sort");
    //console.log("select value: " + selectForm.value);
    if (selectForm.value == 'proximity') {
        //  alert("Proximity sorting is coming soon!");
        imageArray.sort((a, b) => (a.distance > b.distance) ? 1 : -1);
    } else if (selectForm.value == 'age-new') {
        //console.log("sorting newest first");
        imageArray.sort((a, b) => (a.time < b.time) ? 1 : -1);
    } else if (selectForm.value == 'age-old') {
        //console.log("sorting oldest first");
        imageArray.sort((a, b) => (a.time > b.time) ? 1 : -1);
    } else if (selectForm.value == 'recommended') {
        imageArray.sort((a, b) => ((a.zTime*2) + a.zDistance - a.zLikes) > ((b.zTime*2) + b.zDistance - b.zLikes) ? 1 : -1);
    } else if (selectForm.value == 'likes-high') {
        imageArray.sort((a, b) => (a.likes < b.likes) ? 1 : -1);
    } else if (selectForm.value == 'likes-low') {
        imageArray.sort((a, b) => (a.likes > b.likes) ? 1 : -1);
    } else if (selectForm.value == 'comments-high') {
        imageArray.sort((a, b) => (a.commentCount < b.commentCount) ? 1 : -1);
    } else if (selectForm.value == 'comments-low') {
        imageArray.sort((a, b) => (a.commentCount > b.commentCount) ? 1 : -1);
    }

    filterPosts();
}

//var distanceMeanZero = false;
//var likesMeanZero = true;

var accessCode = "";

function showPosts() {
    
    document.getElementById("feed").innerHTML = "<br><div class='loader'></div>";
    //navigator.geolocation.getCurrentPosition(success);

   //console.log("user lat: " + lat + ", user long: " + long);

    var formDataFeed = new FormData();
    
    if (uName == "appTest") {
        //lat = 40.634311;
        //long = -74.908772;
    }
    
    formDataFeed.append('lat', lat);
    formDataFeed.append('long', long);
    
   //console.log("post mode is: " + postMode);
    
    if (postMode == "admin") formDataFeed.append('session', session);

    var xmlhttp = new XMLHttpRequest();

    var getFile = './php/newGetPost.php?_=' + Date.now();
    
    if (postMode == "admin") {
        getFile = './php/getPostAdmin.php?_=' + Date.now();
    }
    
   //console.log("getFile is " + getFile);

    xmlhttp.open('POST', getFile, true);

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4) {
            if (xmlhttp.status == 200) {
                //alert("chenging feed");
                //console.log("changing feed");

               //console.log("response is: " + xmlhttp.responseText);

                if (xmlhttp.responseText == "noLocation") {
                    document.getElementById("feed").innerHTML = "<h2 class='contentText'>Uh oh! We can't tell where you are. Please allow us to access your location and reload.</h2>";
                    //document.getElementById("offsetDiv").style.display = "block";
                } else if (xmlhttp.responseText === "[]") {
                   //console.log("bad response");
                    document.getElementById("feed").innerHTML = "<h2 class='contentText'>Uh oh! It doesn't look like there are any posts near you, try making one.</h2>";
                    //document.getElementById("offsetDiv").style.display = "block";
                    maxDist = 50;
                    document.getElementById("mileRange").max = maxDist * 5280;
                    document.getElementById("mileRange").value = maxDist * 5280;
                    document.getElementById("milesDisplay").innerHTML = maxDist + " miles";
                } else if (xmlhttp.responseText === "[null]") {
                    document.getElementById("feed").innerHTML = "<h2 class='contentText'>Uh oh! It doesn't look like there are any posts near you, try making one.</h2>";
                    //document.getElementById("offsetDiv").style.display = "block";
                    maxDist = 50;
                    document.getElementById("mileRange").max = maxDist * 5280;
                    document.getElementById("mileRange").value = maxDist * 5280;
                    document.getElementById("milesDisplay").innerHTML = maxDist + " miles";
                } else {
                    //document.getElementById("offsetDiv").style.display = "none";


                    //console.log("server response: " + xmlhttp.responseText);
                    
                    imageArray = eval(xmlhttp.responseText);
                    
                    accessCode = imageArray[0].accessCode;
                    
                   //console.log("access code is: " + accessCode);
                    
                    maxDist = 1;
                    
                    if (imageArray.length > 0) timeMin = imageArray[0].minTime;
                    if (imageArray.length > 0 && imageArray[0].maxDist != undefined && imageArray[0].maxDist != null) maxDist = imageArray[0].maxDist;
                    
                   //console.log("max distance is: " + imageArray[0].maxDist);
                    
                    document.getElementById("mileRange").max = maxDist * 5280;
                    document.getElementById("mileRange").value = maxDist * 5280;
                    
                    setCSSProperty();
                    
                    var output = document.getElementById("milesDisplay");
                    output.innerHTML = slider.value/5280 + " mile";
                      
                      if (maxDist < 0.5) {
                        maxDist = maxDist * 5280;
                        if (maxDist == 1) {
                          output.innerHTML = Math.round(maxDist) + " foot";
                        } else {
                          output.innerHTML = Math.round(maxDist) + " feet";
                        }
                      } else {
                        if (maxDist == 1) {
                          output.innerHTML = maxDist.toFixed(2) + " mile";
                        } else {
                          output.innerHTML = maxDist.toFixed(2) + " miles";   
                        }
                      }
                      
                    var distanceSlider = document.getElementById("mileRange");
  
                    distanceInput = distanceSlider.value / 5280;

                    //for (var i = 0; i < imageArray.length; i++) {
                    //    if (imageArray[i].time < timeMin) timeMin = imageArray[i].time;
                    //}

                    document.getElementById("dateStart").min = timeMin;

                    document.getElementById("feed").innerHTML = "";

                    sortImages();
                    initDateFilters();
                    
                    var oldScroll = window.scrollY;
                    
                    //setTimeout(updateUserLikesDisplay(), 1000);
                    
                    var clientHeight = document.getElementById('content').clientHeight;

                    var clientHeightHead = document.getElementById('header').clientHeight;
                    
                    window.addEventListener('scroll', () => {
                        setPadderHeight();
                      //console.log("scrolled", window.scrollY) //scrolled from top
                      //console.log(window.innerHeight) //visible part of screen
                      if (window.scrollY + window.innerHeight + (0.5 * window.innerHeight) >= document.documentElement.scrollHeight) {
                        fillPics(imagesToDisplay);
                       //console.log("we in da loop");
                      }
                      
                      //console.log("new scroll: " + window.scrollY);
                      //console.log("old scroll: " + oldScroll);
                      
                        var clientHeight = document.getElementById('content').clientHeight;

                        var clientHeightHead = document.getElementById('header').clientHeight;

                        let height = window.innerHeight;
                        
                        var hasOverflowHeight = (height - clientHeight - clientHeightHead) <= 0;
                      
                        if (window.scrollY - oldScroll < 0 && !fading && !isShown && ((window.innerHeight + window.scrollY) < document.body.offsetHeight)) {
                            //document.getElementById("footer").style.display = "block";
                            unfade();
                        }
                      
                        if (window.scrollY - oldScroll > 0 && !fading && isShown && window.scrollY > 10 && hasOverflowHeight) {
                          //document.getElementById("footer").style.display = "none";
                          fadeOutFoot();
                        }
                      
                        oldScroll = window.scrollY;
                      
                      //scrollHeader();
                    })
                    
                    window.onresize = function() { 
                        var count = 0;
                        
                        while (window.innerHeight > document.body.clientHeight && count <= 20 && maxNotHit) {
                            if (imageArray.length > 0) fillPics(imagesToDisplay);
                        setPadderHeight();
                        count++;
                        }
                    };
                    
                }
            }
        }
    };
    
    xmlhttp.send(formDataFeed);

}

//alert("Uh oh! It looks like you aren't logged in.");
//makeFormLogin();
//showLogin();

function showPostsByMe() {
    document.getElementById("feed").innerHTML = "<br><div class='loader'></div>";

    var formDataFeed = new FormData();
    
    formDataFeed.append('lat', lat);
    formDataFeed.append('long', long);
    formDataFeed.append('session', session);
    if (uName != "") formDataFeed.append('uname', uName);

    var xmlhttp = new XMLHttpRequest();

    var getFile = './php/getAllUserPosts.php?_=' + Date.now();
    
   //console.log("getFile is " + getFile);

    xmlhttp.open('POST', getFile, true);

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4) {
            if (xmlhttp.status == 200) {
                //alert("chenging feed");
                //console.log("changing feed");

            //  //console.log("response is: " + xmlhttp.responseText);

                if (xmlhttp.responseText == "noLocation") {
                    document.getElementById("feed").innerHTML = "<h2 class='contentText'>Uh oh! We can't tell where you are. Please allow us to access your location and reload.</h2>";
                    //document.getElementById("offsetDiv").style.display = "block";
                } else if (xmlhttp.responseText === "[]") {
                   //console.log("bad response");
                    document.getElementById("feed").innerHTML = "<h2 class='contentText'>Uh oh! It doesn't look like there are any posts near you, try making one.</h2>";
                    //document.getElementById("offsetDiv").style.display = "block";
                } else if (xmlhttp.responseText === "invalidSession") {
                    document.getElementById("feed").innerHTML = "<h2 class='contentText'>Uh oh! We couldn't verify your session.</h2>";
                } else if (xmlhttp.responseText === "[null]") {
                    document.getElementById("feed").innerHTML = "<h2 class='contentText'>Uh oh! It doesn't look like there are any posts near you, try making one.</h2>";
                    //document.getElementById("offsetDiv").style.display = "block";
                } else {
                    
                    imageArray = eval(xmlhttp.responseText);
                    
                    accessCode = imageArray[0].accessCode;

                    maxDist = 1;
                    
                    if (imageArray.length > 0) timeMin = imageArray[0].minTime;
                    if (imageArray.length > 0 && imageArray[0].maxDist != undefined && imageArray[0].maxDist != null) maxDist = imageArray[0].maxDist;
                    
                    document.getElementById("mileRange").max = maxDist * 5280;
                    document.getElementById("mileRange").value = maxDist * 5280;
                    
                    setCSSProperty();
                    
                    var output = document.getElementById("milesDisplay");
                    output.innerHTML = slider.value/5280 + " mile";
                      
                      if (maxDist < 0.5) {
                        maxDist = maxDist * 5280;
                        if (maxDist == 1) {
                          output.innerHTML = Math.round(maxDist) + " foot";
                        } else {
                          output.innerHTML = Math.round(maxDist) + " feet";
                        }
                      } else {
                        if (maxDist == 1) {
                          output.innerHTML = maxDist.toFixed(2) + " mile";
                        } else {
                          output.innerHTML = maxDist.toFixed(2) + " miles";   
                        }
                      }
                      
                    var distanceSlider = document.getElementById("mileRange");
  
                    distanceInput = distanceSlider.value / 5280;

                    document.getElementById("dateStart").min = timeMin;

                    document.getElementById("feed").innerHTML = "";

                    sortImages();
                    
                    initDateFilters();
                    
                }
            }
        }
    };
    
    xmlhttp.send(formDataFeed);

}

var fadeTimeout = 0;

var isShown = true;

function unfade() {
    fading = true;
    var op = 0.1;  // initial opacity
    document.getElementById("footer").style.display = 'block';
    var timer = setInterval(function () {
        if (op >= 1){
            clearInterval(timer);
            fading = false;
            isShown = true;
        }
        document.getElementById("footer").style.opacity = op;
        document.getElementById("footer").style.filter = 'alpha(opacity=' + op * 100 + ")";
        op += op * 0.1;
    }, 1);
}

var fading = false;

function fadeOutFoot() {
    fading = true;
    var op = 1;  // initial opacity
    var timer = setInterval(function () {
        if (op <= 0.1){
            clearInterval(timer);
            document.getElementById("footer").style.display = 'none';
            fading = false;
            isShown = false;
        }
        document.getElementById("footer").style.opacity = op;
        document.getElementById("footer").style.filter = 'alpha(opacity=' + op * 100 + ")";
        op -= op * 0.1;
    }, 10);
}

var counter = 0;

var maxNotHit = true;

function display(jsonInfo) {
   //console.log("json info: " + jsonInfo);
   //console.log("json info length: " + jsonInfo.length);
    
    if (jsonInfo.length == 0) {
        //document.getElementById("offsetDiv").style.display = "block";
        document.getElementById("feed").innerHTML = "<h2 class='contentText'>Uh oh! It doesn't look like there are any posts near you, try making one.</h2>";
    } else {
        document.getElementById("feed").innerHTML = "";
        //document.getElementById("offsetDiv").style.display = "none";
    }
    
    counter = 0;
    
    imagesToDisplay = jsonInfo;
    
   //console.log("i that shit");
    //console.log("document height: " + document.body.clientHeight);
    
    PostsDisplayed = 0;
    
    // for (var i=0; i <= 3; i++) {
    //     fillPics(jsonInfo);
    // }
    
    // while (window.innerHeight > document.body.clientHeight) {
    //     fillPics(jsonInfo);
    // }
    
    var count = 0;
    
    maxNotHit = true;
    
    //var profilePostId = -1;

    //var postIdDisplayed = false;
    
   //console.log("profilePostId " + profilePostId);
   //console.log("postIdDisplayed " + postIdDisplayed);
    
    postIdDisplayed = false;
    profileShouldScroll = true;
    // profilePostId = -1;
    
    while ((window.innerHeight * 1.5 > document.documentElement.scrollHeight && count < 10 && maxNotHit) || (profilePostId != -1) && !postIdDisplayed) {
        fillPics(imagesToDisplay);
       //console.log("we in da display");
        count++;
    }
    
    if (count <= window.innerHeight / (window.innerWidth * 0.8)) {
        profileShouldScroll = false;
    }
    
}

function checkLikesLoad(jsonInfo) {
    
   //console.log("in check likes load");
    
    /*
    
    if (likedPosts == "nill") {
        setTimeout(updateUserLikesDisplay(), 2000);
    } else {
        
        fillPics(jsonInfo);
        
        var x = 1;
        
        while (window.innerHeight > (window.innerWidth * 3 * x)) {
            fillPics(jsonInfo);
            x++;
        }
    }
    
    */
}

var PostsDisplayed = 0;

var lastPostDisplayed = -1;

function fillPics(jsonInfo) {
        var i = 0;
        
        if (counter >= jsonInfo.length) {
            maxNotHit = false;
            postIdDisplayed = true;
            profilePostId = -1;
        }
    
        while (i < 1 && counter < jsonInfo.length) {

        //var profilePostId = -1;

        //var postIdDisplayed = false;

        var object = jsonInfo[counter];
        
        lastPostDisplayed = object.id;
        
        if (object.id == profilePostId) postIdDisplayed = true;
        
        //console.log("object image: " + object.image);
        ////console.log("object lat: " + object.lat);
        ////console.log("object long: " + object.long);
        
        var distanceNum = object.distance;
        var distanceString = "";

        var isFeet = false;

        if (distanceNum < 0.5) {
            distanceNum = Math.round(distanceNum * 5280);
            isFeet = true;
        } else if (distanceNum < 5) {
            distanceNum = distanceNum.toFixed(2);
        } else {
            distanceNum = distanceNum.toFixed(0);
        }

        if (isFeet) {
            if (distanceNum == 1) {
                distanceString += distanceNum + " foot";
            } else {
                distanceString += displayNum(distanceNum, false) + " feet";
            }
        } else if (distanceNum < 5) {
            if (distanceNum == 1) {
                distanceString += distanceNum + " mile";
            } else {
                distanceString += distanceNum + " miles";   
            }
        } else {
            distanceNum = displayNum(distanceNum, true);
            distanceString += distanceNum + " miles";
        }

        var timeServer = object.time;

        var milliseconds = timeServer * 1000;

        var dateObj = new Date(milliseconds);
        var month = dateObj.getUTCMonth() + 1; //months from 1-12
        var day = dateObj.getUTCDate();
        var year = dateObj.getUTCFullYear();

        var newdate = month + "/" + day + "/" + year;
        
        var heartClass = "fa-regular";
        var flagClass = "fa-flag";
        
        if (likedPosts.includes(object.id)) heartClass = "fa-solid";
        if (object.user == userId) flagClass = "fa-eye-slash";
        
        var postHasComments = false;
        
        if (object.comments !== null && object.comments !== undefined) {
            var commentsTest = JSON.parse(object.comments);
            postHasComments = commentsTest.length > 0;
        }

        ////console.log(blockedUsers);
        ////console.log(JSON.stringify(blockedUsers));

        if (object.hidden != true && !blockedUsers.includes(object.user) && (jsonInfo[counter-1] == null || jsonInfo[counter].id != jsonInfo[counter-1].id) && !deletedPosts.includes(object.id)) {
            
            ////console.log("post was posted by: " + object.postedBy);
            
            var userName = object.postedBy;
            
            if (userName == "Noah" || userName == "1") userName = userName + ' <i class="fa-solid fa-circle-check"></i>';
            
            var feedString = "<div id='postContainter" + object.id + "' class='postContainer'>";
            
            if (profileFilter == -1) feedString += "<p id='profile" + object.id + "' class='userText' onclick='showProfile(" + object.user + ", " + object.id + ")'>"+ userName + "  <i class='fa-solid fa-user'></i></p>";
            
            var sessionInputString = "";
            
            if (postMode == "admin") {
                sessionInputString = accessCode;
            }
            
           //console.log("post likes " + object.likes);
           //console.log("display post likes " + displayNum(object.likes, false));//parent.innerWidth
           //document.getElementById(\"img" + object.id + "\").style.background-color = \"var(--postBG)\";
           
           //<img id=\"loadingOverlay" + object.id + "\" class=\"loader overlayFeed src='./assets/loading.gif'>
           //loading='lazy'

            feedString += "<div class='imageWrapper'><div id='loadingOverlay" + object.id + "' class='dot-flashing overlayFeed'></div><img id='" + "img" + object.id + "' src='./posts/post" + object.id + ".png' style=\" background-repeat: no-repeat; background-position: center; background-origin: padding-box padding-box; background-size:" + parent.innerWidth + " " + parent.innerWidth + "; background: url(\'./posts/smallPost" + object.id + ".jpg\'); background-size:100% 100%;\" onload='imageLoaded(" + object.id + ");'></div> <div id='" + "cap" + object.id + "'class='caption'><div class='capLeft'><p> <span id='locationSpan'><i class='fa-solid fa-location-dot'></i> " + distanceString + "</span> <i class='fa-solid fa-calendar'></i> " + newdate + " </p></div><div class='capRight'><p> <span id='likes" + object.id + "'>" + displayNum(object.likes, false) + "</span> <i id='heart" + object.id + "' onclick='likePost(" + object.id + ")' class='" + heartClass + " fa-heart'></i>  <i id='flag" + object.id + "' class='fa-solid " + flagClass + "' onclick='flagPost(" + object.id + ")'></i></p></div>";
            
            var styleAlt = "display: none;";
            var commentButtonString = " - <span id='showComm" + object.id + "'>Show</span><span id='hideComm" + object.id + "' style='display:none;'>Hide</span> <span id='countComm" + object.id + "'>" + displayNum(object.commentCount, true) + "</span> Comment<span id='multiComm" + object.id + "'>s</span> - ";
            
            if (object.commentCount == 1) {
            //     commentButtonString = " - Show " + displayNum(object.commentCount, true) + " Comment - ";
                commentButtonString = " - <span id='showComm" + object.id + "'>Show</span><span id='hideComm" + object.id + "' style='display:none;'>Hide</span> <span id='countComm" + object.id + "'>" + displayNum(object.commentCount, true) + "</span> Comment<span id='multiComm" + object.id + "' style='display: none;'>s</span> - ";
            } 
            
            if (object.id == openComments) {
                styleAlt = "";
                commentButtonString = " - <span id='showComm" + object.id + "' style='display:none;'>Show</span><span id='hideComm" + object.id + "' >Hide</span> <span id='countComm" + object.id + "'>" + displayNum(object.commentCount, true) + "</span> Comment<span id='multiComm" + object.id + "'>s</span> - ";
                
                if (object.commentCount == 1) {
                //     commentButtonString = " - Show " + displayNum(object.commentCount, true) + " Comment - ";
                    commentButtonString = " - <span id='showComm" + object.id + "' style='display:none;'>Show</span><span id='hideComm" + object.id + "'>Hide</span> <span id='countComm" + object.id + "'>" + displayNum(object.commentCount, true) + "</span> Comment<span id='multiComm" + object.id + "' style='display: none;'>s</span> - ";
                } 
            }
            
            feedString += "<div id='comments" + object.id + "' style='" + styleAlt + "' class='commentsBox'>";
            
            if (object.comments !== null && object.comments !== undefined) {
                var comments = JSON.parse(object.comments);
                ////console.log("comments: " + object.comments);
                
                //usersBlockedMe
                
                var displayedComm = 0;
                
                for (var i=0; i < comments.length; i++) {
                    if (comments[i].hidden != true && !usersBlockedMe.includes(comments[i].user) && !blockedUsers.includes(comments[i].user)) {
                        
                        var username = "";
                        
                        feedString += "<div id='comment" + comments[i].id + "post" + object.id + "'>";
                        
                        // for (var x=0; x < allUsers.length; x++) {
                        //     ////console.log("users id: " + allUsers[x].id);
                        //     ////console.log("comments user: " + comments[i].user);
                        //     if (allUsers[x].id == comments[i].user) {
                        //         username = allUsers[x].user;
                        //     }
                        // }
                        
                        if (userName == "Noah" || userName == "1") comments[i].userName = comments[i].userName + ' <i class="fa-solid fa-circle-check"></i>';
                        
                        var deleteButton = "";
                        
                        if (currUserId == comments[i].user) {
                            deleteButton = "<i class='fa-solid fa-trash commentBttnDel' onclick='flagComm(" + object.id + "," + comments[i].id + ")'></i>";
                        } else {
                           //console.log("comment id: " + comments[i].id);
                            deleteButton = "<i class='fa-solid fa-flag commentBttnDel' onclick='flagComm(" + object.id + "," + comments[i].id + ")'></i>";
                        }
                        
                        if (i != 0 && displayedComm != 0) feedString += "<div class='spacerComments'></div>";
                        
                        feedString += "<div class='commentLeft'><p><span onclick='showProfile(" + comments[i].user + ", " + object.id + ")' class='commUName'>" + comments[i].userName + ": </span><span class='commentText'>" + comments[i].comment + "</span></p></div><div class='commentRight'>" + deleteButton + "</div>";
                        
                        feedString += "</div>";
                        
                        displayedComm++;
                    }
                }
            }
            
            postHasComments = displayedComm > 0;
            
            var style = "";
            
            if (!postHasComments) {
               feedString += "<p class='commentText' style='text-align: center;'>This post doesn't have any comments yet</p>";
            }
            
            feedString += "</div>";
            
            feedString += "<br><div class='commentArea'><input class='halfFields commentInput' type='text' name='comment' id='addComment" + object.id + "' placeholder='Write a comment...'><i id='commentsBttn" + object.id + "' onclick='postComment(" + object.id + ")' class='fa-solid fa-paper-plane commentsBttn'></i></div><button id='commentsToggle" + object.id + "' onclick='toggleComments(" + object.id + ")' class='showComments' style='" + style + "'>" + commentButtonString + "</button>";
            
            ////<i class="fa-solid fa-paper-plane"></i>
            
            if (object.visibility == "following" && postMode == "admin") {
                feedString += "<br><br><span style='text-align: center; width: 100%;'>This post can only be seen by people this user follows</span>";
            }
            
            feedString += "</p></div>";
            
            document.getElementById("feed").innerHTML += feedString;
            
            //document.getElementById("feed").innerHTML += "<img src='./posts/" + object.image + "' width='100%'> <div class='caption'><div class='capLeft'><p> <span id='locationSpan'><i class='fa-solid fa-location-dot'></i> " + distanceString + "</span> <i class='fa-solid fa-calendar'></i> " + newdate + " </p></div><div class='capRight'><p><strong>" + object.postedBy + "</strong> <span id='likes" + object.id + "'>" + object.likes + "</span> <i id='heart" + object.id + "' onclick='likePost(" + object.id + ")' class='" + heartClass + " fa-heart'></i>  <i class='fa-solid fa-flag' onclick='flagPost(" + object.id + ")'></i></p></div>" ;
        
            i++;
            
            PostsDisplayed ++
            
            try {
                refreshComments();
            } catch (err) {
                //lol you thought I was gonna do something
            }
        
        } else {
           //console.log("post #" + object.id + " has been flagged!");
        }

        //var likeString = "like" + object.id;

        //document.getElementById(likeString).addEventListener("click", likePost(object.id));
        
        counter++;
        
        if (counter >= jsonInfo.length && PostsDisplayed == 0) {
            document.getElementById("feed").innerHTML = "<h2 class='contentText'>Uh oh! It doesn't look like there are any posts near you, try making one.</h2>";
        }
        
        setPadderHeight();
    }
}

function imageLoaded(postId) {
    // document.getElementById("img" + postId).style = "";
    try {
        document.getElementById("loadingOverlay" + postId).style.display = "none";
    } catch(err) {
        
    }
}

var postToFlag = -1;
var flagReason = "other";

function flagPostOption() {
    flagReason = document.getElementById("flagOptions").value;
    
   //console.log("reason: " + flagReason);
}

function flagPost(postId) {
    
    if (session !== "") {
    
        postToFlag = postId;
        
       //console.log("flagging #" + postToFlag);
        
        //if (document.getElementById("flagDiv").style.display == "block") {
        //    document.getElementById("flagDiv").style.display = "none";
            ////showingAbout = false;
        //} else {
            hideWindows();
            
            var userPosted = false;
            var postObj;
            
            for (var v=0; v < imageArray.length; v++) {
                if (postToFlag == imageArray[v].id) {
                    userPosted = (imageArray[v].user == userId);
                    postObj = imageArray[v];
                }
            }
            
            if (userPosted) {
               //console.log("User Posted");
                document.getElementById("deleteContent").style.display = "block";
                document.getElementById("flagContent").style.display = "none";//Remove Post
                document.getElementById("flagTitle").innerHTML = "Hide Post";
                //document.getElementById("flagTitle").style.paddingRight = "55%";
                if (postObj.visibility == "following") document.getElementById("editPostVis").value = "following";
            } else {
                document.getElementById("deleteContent").style.display = "none";
                document.getElementById("flagContent").style.display = "block";
                document.getElementById("flagTitle").innerHTML = "Flag Post";
                //document.getElementById("flagTitle").style.paddingRight = "66%";
            }
            
            document.getElementById("flagDiv").style.display = "block";
            
            ////showingAbout = true;
        //}
        
        //while (window.innerHeight > document.body.clientHeight) {
        //    if (imageArray.length > 0) fillPics(imagesToDisplay);
        //}
    
    } else {
        alert("Uh oh! It looks like you aren't logged in.");
        makeFormLogin();
        showLogin();
        
        // while (window.innerHeight > document.body.clientHeight) {
        //     try {
        //         if (imageArray.length > 0) fillPics(imagesToDisplay);
        //     } catch(err) {
        //         //document.getElementById("offsetDiv").style.display = "block";
        //     }
        // }
    }
}

var postCommFlag = -1;
var commFlag = -1;

function flagComm(postId, commId) {
    
    if (session !== "") {
    
        postCommFlag = postId;
        commFlag = commId;
        
            hideWindows();
            
            var userPosted = false;
            var postObj;
            
            for (var v=0; v < imageArray.length; v++) {
                if (postCommFlag == imageArray[v].id) {
                    var comments = JSON.parse(imageArray[v].comments);
                    
                    for (var x=0; x < comments.length; x++) {
                        if (comments[x].id == commFlag) {
                            userPosted = (comments[x].user == userId);
                        }
                    }
                }
            }
            
            if (userPosted) {
               //console.log("User Posted");
                document.getElementById("deleteCommContent").style.display = "block";
                document.getElementById("flagCommContent").style.display = "none";//Remove Post
                document.getElementById("flagCommTitle").innerHTML = "Delete Comment";
                //document.getElementById("flagTitle").style.paddingRight = "55%";
            } else {
                document.getElementById("deleteCommContent").style.display = "none";
                document.getElementById("flagCommContent").style.display = "block";
                document.getElementById("flagCommTitle").innerHTML = "Flag Comment";
                //document.getElementById("flagTitle").style.paddingRight = "66%";
            }
            
            document.getElementById("flagCommDiv").style.display = "block";
            
            ////showingAbout = true;
        //}
        
        //while (window.innerHeight > document.body.clientHeight) {
        //    if (imageArray.length > 0) fillPics(imagesToDisplay);
        //}
    
    } else {
        alert("Uh oh! It looks like you aren't logged in.");
        makeFormLogin();
        showLogin();
    }
}

function toggleComments(id) {
    
    //if (refreshTime != null) {
    //    clearTimeout(refreshTime);
    //    refreshTime = null;
    //}
    
    if (document.getElementById("comments" + id).style.display == "none") {
        document.getElementById("comments" + id).style.display = "block";
        // document.getElementById("commentsToggle" + id).innerHTML = " - Hide Comments - ";
        
        document.getElementById("showComm" + id).style.display = "none";
        document.getElementById("hideComm" + id).style.display = "inline-block";
        
        if (openComments != -1) {
            document.getElementById("comments" + openComments).style.display = "none";
            // document.getElementById("commentsToggle" + openComments).innerHTML = " - Show Comments - ";
            document.getElementById("showComm" + id).style.display = "inline-block";
            document.getElementById("hideComm" + id).style.display = "none";
        }
        
        openComments = id;
        
        refreshComments();
    } else {
        document.getElementById("comments" + id).style.display = "none";
        // document.getElementById("commentsToggle" + id).innerHTML = " - Show Comments - ";
        
        document.getElementById("showComm" + id).style.display = "inline-block";
        document.getElementById("hideComm" + id).style.display = "none";
        
        openComments = -1;
    }
}

function deleteComment(id, commentId) {
    
   //console.log("trying to flag #" + postToFlag + " for: " + flagReason);
    if (session !== "") {
        
        var formData = new FormData();
        formData.append("id", postCommFlag);
        formData.append("commentId", commFlag);
        formData.append("session", session);
    
        var xhttp = new XMLHttpRequest();
    
        // Set POST method and ajax file path
        xhttp.open("POST", "./php/deleteComment.php?_='" + Date.now(), true);
    
        // call on request changes state
        xhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
    
                var response = this.responseText;
                
                //console.log("response for comment: " + response);
                
                if (response == 1) {
                    hideWindows();
                    refreshComments();
                } else {
                   //console.log("Flag Failed");
                    alert("Your comment couldn't be deleted. Please try again later");
                }
            }
        };
    
        xhttp.send(formData);
    
    } else {
        alert("Uh oh! It looks like you aren't logged in.");
        makeFormLogin();
        showLogin();
    }
}

function flagComment() {
    
   //console.log("trying to flag #" + postToFlag + " for: " + flagReason);
    if (session !== "") {
        
        var formData = new FormData();
        formData.append("id", postCommFlag);
        formData.append("commentId", commFlag);
        formData.append("reason", document.getElementById("flagCommOptions").value);
        formData.append("session", session);
    
        var xhttp = new XMLHttpRequest();
    
        // Set POST method and ajax file path
        xhttp.open("POST", "./php/flagComment.php?_='" + Date.now(), true);
    
        // call on request changes state
        xhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
            var response = this.responseText;
            if (response == 1) {
                    
               //console.log("Post Flagged");
                
                hideWindows();
                refreshComments();
                
                alert("The comment has been flagged! It will now be hidden until it is reviewed");
                
                } else if (response == "emailNotConfirmed") {
                    //console.log("Like Failed");
                    alert("Uh oh! You need to confirm your email before you can flag comments");
                            
                    //liking = false;
                } else if (response == "alreadyChecked") {
                    //console.log("Like Failed");
                    alert("Sorry! We've already determined that this comment doesn't violate that guidline.");
                            
                    //liking = false;
                } else {
                   //console.log("Flag Failed");
                    alert("The comment couldn't be flagged. Please try again later");
                    
                }
            }
        };
    
        xhttp.send(formData);
    
    } else {
        alert("Uh oh! It looks like you aren't logged in.");
        makeFormLogin();
        showLogin();
    }
}

var sendingComment = false;

function postComment(id) {
    
   //console.log("trying to flag #" + postToFlag + " for: " + flagReason);
    if (session !== "") {
        
        if (!sendingComment) {
        
            var formData = new FormData();
            formData.append("id", id);
            formData.append("comment", document.getElementById("addComment" + id).value);
            formData.append("session", session);
        
            var xhttp = new XMLHttpRequest();
        
            // Set POST method and ajax file path
            xhttp.open("POST", "./php/addComment.php?_='" + Date.now(), true);
        
            // call on request changes state
            xhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
        
                    var response = this.responseText;
                    
                    sendingComment = false;
                    
                   //console.log("response for comment: " + response);
                    
                    if (response.charAt(0) == '{') {
                        
                        responseObj = JSON.parse(response);
                        
                        deleteButton = "<i class='fa-solid fa-trash commentBttnDel' onclick='flagComm(" + openComments + "," + responseObj.id + ")'></i>";
                        
                        var divide = "";
                        
                        if (document.getElementById("comments" + id).innerHTML.length > 0) {
                            divide = "<br>";
                        }
                        
                        var commentString = "<div id='comment" + responseObj.id + "post" + id + "'>" + divide + "<div class='commentLeft'><p>" + uName + ": " + responseObj.comment + "</p></div><div class='commentRight'>" + deleteButton + "</div></div>";
                        
                        var commentFillerString = "<p class='commentText' style='text-align: center;'>This post doesn't have any comments yet</p>";
                        
                       //console.log(document.getElementById("comments" + id).innerHTML);
                        
                        // if (document.getElementById("countComm" + id).innerHTML == 0) {
                        //     document.getElementById("comments" + id).innerHTML  = commentString;
                        //     console.log("First comment!");
                        // } else {
                        //     document.getElementById("comments" + id).innerHTML  += commentString;
                        // }
                        
                        //imageArray
                        
                        commentCount++;
                        
                        document.getElementById("commentCount").innerHTML = displayNum(commentCount, false);
                        
                        for (var c=0; c < imageArray.length; c++) {
                            if (imageArray[c].id == id) {
                                if (imageArray[c].comments != null && imageArray[c].comments != undefined) {
                                    var commentsAr = JSON.parse(imageArray[c].comments)
                                    commentsAr.push(responseObj);
                                    imageArray[c].comments = JSON.stringify(commentsAr);
                                } else {
                                    imageArray[c].comments = JSON.stringify([responseObj]);
                                }
                            }
                        }
                        
                        document.getElementById("comments" + id).style.display = "block";
                        // document.getElementById("commentsToggle" + id).innerHTML = " - Hide Comments - ";
                        
                        document.getElementById("showComm" + id).style.display = "none";
                        document.getElementById("hideComm" + id).style.display = "inline-block";
                        
                        
                            if (openComments != -1) {
                                document.getElementById("comments" + openComments).style.display = "none";
                                // document.getElementById("commentsToggle" + openComments).innerHTML = " - Show Comments - ";
                                document.getElementById("showComm" + openComments).style.display = "inline-block";
                                document.getElementById("hideComm" + openComments).style.display = "none";
                                openComments = -1;
                            }
                        
                        
                        openComments = id;
                        
                        document.getElementById("comments" + id).style.display = "block";
                        // document.getElementById("commentsToggle" + id).innerHTML = " - Hide Comments - ";
                        document.getElementById("showComm" + id).style.display = "none";
                        document.getElementById("hideComm" + id).style.display = "inline-block";
                        document.getElementById("commentsToggle" + id).style.display = "block";
                        
                        refreshComments();
                        
                        var objDiv = document.getElementById("comments" + id);
                        objDiv.scrollTop = objDiv.scrollHeight;
                        
                        sendingComment = false;
                        
                        document.getElementById("addComment" + id).value = "";
                        
                        //add the comment to the normal comments zone
                    } else if (response == "noComment") {
        
                    } else if (response == "emailNotConfirmed") {
                        alert("Uh oh! You need to confirm your email before you can like posts");
                    } else if (response == "tooLong") {
                        alert("It looks like your comment is a little too long, try shortening it!");
                    } else if (response == "containsBlocked") {
                        alert("It looks like there are some vulgar words in your comment, please change the comment and try again");
                    } else {
                       //console.log("Flag Failed");
                        alert("Your comment failed. Please try again later");
                    }
                } else {
                    sendingComment = false;
                }
            };
        
            xhttp.send(formData);
            
            sendingComment = true;
        
        }
        
    } else {
        alert("Uh oh! It looks like you aren't logged in.");
        makeFormLogin();
        showLogin();
    }
}

refreshComments();

var refreshTime = null;

function refreshComments() {
    
    if (refreshTime != null) {
        clearTimeout(refreshTime);
        refreshTime = null;
    }
    
    //for (var i=0; i < openComments.length; i++) {
        
        if (openComments != -1) {
            
            var formData = new FormData();
            formData.append("id", openComments);
    
            var xhttp = new XMLHttpRequest();
        
            // Set POST method and ajax file path
            xhttp.open("POST", "./php/getPostsComments.php?_='" + Date.now() + (Math.random() * 1000), true);
        
            // call on request changes state
            xhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
    
                var response = this.responseText;
                
               //console.log("refresh response: " + response);
                
                if (response.charAt(0) == '[') {
                    var postsComments = JSON.parse(response);
                    
                    var feedString = "";
                    
                    var displayed = 0;
                    
                    // if (postsComments.length <= 0) {
                    //     document.getElementById("comments" + id).style.display = "none";
                    //     document.getElementById("commentsToggle" + id).style.display = "none";
                    // }
                    
                    for (var i=0; i < postsComments.length; i++) {
                        if (postsComments[i].hidden != true && !usersBlockedMe.includes(postsComments[i].user) && !blockedUsers.includes(postsComments[i].user)) {
                            
                            var username = "";
                            
                            feedString += "<div class='commentEntry' id='comment" + postsComments[i].id + "post" + openComments + "'>";
                            
                            // for (var x=0; x < allUsers.length; x++) {
                            //     ////console.log("users id: " + allUsers[x].id);
                            //     ////console.log("comments user: " + comments[i].user);
                            //     if (allUsers[x].id == comments[i].user) {
                            //         username = allUsers[x].user;
                            //     }
                            // }
                            
                            if (postsComments[i].userName == "Noah" || postsComments[i].userName == "1") postsComments[i].userName = postsComments[i].userName + ' <i class="fa-solid fa-circle-check"></i>';
                            
                            var deleteButton = "";
                            
                            if (currUserId == postsComments[i].user) {
                                deleteButton = "<i class='fa-solid fa-trash commentBttnDel' onclick='flagComm(" + openComments + "," + postsComments[i].id + ")'></i>";
                            } else {
                                deleteButton = "<i class='fa-solid fa-flag commentBttnDel' onclick='flagComm(" + openComments + "," + postsComments[i].id + ")'></i>";
                            }
                            
                            if (i != 0 && displayed != 0) feedString += "<div class='spacerComments'></div>";
                            
                            feedString += "<div class='commentLeft'><p><span onclick='showProfile(" + postsComments[i].user + ", " + openComments + ")' class='commUName'>" + postsComments[i].userName + ": </span><span class='commentText'>" + postsComments[i].comment + "</span></p></div><div class='commentRight'>" + deleteButton + "</div>";
                            
                            //feedString += "<div class='commentLeft'><p><span onclick='showProfile(" + comments[i].user + ")' class='commUName'>" + comments[i].userName + " <span class='commentText'>" + comments[i].comment + "</span></p></div><div class='commentRight'>" + deleteButton + "</div>";
                            
                            feedString += "</div>";
                            
                            displayed++;
                        }
                    }
                    
                    if (displayed == 0) {
                        feedString += "<p class='commentText' style='text-align: center;'>This post doesn't have any comments yet</p>";
                    }
                    
                    document.getElementById("countComm" + openComments).innerHTML = displayNum(displayed, true);
                    
                    if (displayed == 1) {
                        document.getElementById("multiComm" + openComments).style.display = "none";   
                    } else {
                        document.getElementById("multiComm" + openComments).style.display = "inline-block";   
                    }
                    
                    //console.log("the comments thing is: " + openComments[iHolder]);
                    //console.log("the comments thing is 2: " + openComments);
                    //console.log("the comments thing is 3: " + iHolder);
                    
                    document.getElementById("comments" + openComments).innerHTML = feedString;
                    
                }
            }};
            //console.log("i is: " + storeI);
        
            xhttp.send(formData);
            
            refreshTime = setTimeout(function (){ refreshComments(); }, 10000);
        
        }
}

function executeFlag() {
    
   //console.log("trying to flag #" + postToFlag + " for: " + flagReason);
    
    var formData = new FormData();
    formData.append("id", postToFlag);
    formData.append("reason", flagReason);
    formData.append("session", session);

    var xhttp = new XMLHttpRequest();

    // Set POST method and ajax file path
    xhttp.open("POST", "./php/flagPost.php?_='" + Date.now(), true);

    // call on request changes state
    xhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {

            var response = this.responseText;
            if (response == 1) {
                    
               //console.log("Post Flagged");
                
                hideWindows();
                
                alert("The post has been flagged! It will now be hidden until it is reviewed");
                
                document.getElementById("postContainter" + postToFlag).style.display = "none";
                
                deletedPosts.push(postToFlag);
                
                if (document.getElementById("name").innerHTML !== "bigmas" || document.getElementById("name").innerHTML !== "johnrey1998") {
                    sendUpdate("Alert! " + document.getElementById("name").innerHTML + " just flagged a post! click this link to review it: https://geocam.app/admin/review <@&984817096171061289>");   
                } else {
                    sendUpdate("Alert! " + document.getElementById("name").innerHTML + " just flagged a post! click this link to review it: https://geocam.app/admin/review, since this was flagged by a founder no @ mention is neccessary");   
                }
                
            } else if (response == "emailNotConfirmed") {
                //console.log("Like Failed");
                alert("Uh oh! You need to confirm your email before you can flag posts");
                        
                //liking = false;
            } else if (response == "alreadyChecked") {
                //console.log("Like Failed");
                alert("Sorry! We've already determined that this post doesn't violate that guidline.");
                        
                //liking = false;
            } else {
               //console.log("Flag Failed");
                alert("The post couldn't be flagged. Please try again later");
                
            }
        }
    };

    xhttp.send(formData);
}

function executeDeletePost() {
    
    //console.log("testing account creation");
    
    var formDataAcc = new FormData();
    formDataAcc.append("postId", postToFlag);
    formDataAcc.append("session", session);
    formDataAcc.append("pass", document.getElementById("passDeletePost").value);

    var xhttpAcc = new XMLHttpRequest();

    // Set POST method and ajax file path
    xhttpAcc.open("POST", "./php/deletePost.php?_='" + Date.now(), true);
    
    // call on request changes state
    xhttpAcc.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {

            var responseText = this.responseText;
            
         ////console.log("getUser gotten");
            
         //console.log("executeDeletePost: " + responseText);
            
            //setCookie("codeUser", response, 999999);
            
            if (responseText == 1) {
                //session = responseText;
                
                //setCookie("session", responseText, 1);
                
                //alert("You have been logged in!");
                
                //showLoggedIn();
                
                //checkSession(false);
                
                alert("Your post has been deleted!");
                
                document.getElementById("postContainter" + postToFlag).style.display = "none";
                
                deletedPosts.push(postToFlag);
                
                // will become issue if user ever makes over 1000 posts, just don't store in html use var
                
                postCount--;
                
                document.getElementById("postCount").innerHTML = displayNum(postCount, false);
                
                sendUpdate("Whoops! " + document.getElementById("name").innerHTML + " just deleted a post! They've now made " + document.getElementById("postCount").innerHTML + " posts");
                
                //checkSession(false);
                hideWindows();
                
            } else {
                alert("Uh oh that didn't work, please make sure that you entered the right password");
            }

            document.getElementById("deleteSlider").value = 2;
            setCSSPropertyAccount();
            
            var count = 0;
                        
            while (window.innerHeight > document.body.clientHeight && count <= 20 && maxNotHit) {
                if (imageArray.length > 0) fillPics(imagesToDisplay);
            setPadderHeight();
            count++;
            }

            //likedPosts = JSON.parse(responseText);
            
            //printSession(responseText);
            
            //setCookie("session", responseText, 1);
            
        }
    };

    xhttpAcc.send(formDataAcc);
}

function executeChangePostVis() {
    
    //console.log("testing account creation");
    
    var formDataAcc = new FormData();
    formDataAcc.append("postId", postToFlag);
    formDataAcc.append("session", session);
    formDataAcc.append("vis", document.getElementById("editPostVisSelect").value);

    var xhttpAcc = new XMLHttpRequest();

    // Set POST method and ajax file path
    xhttpAcc.open("POST", "./php/changePostVis.php?_='" + Date.now(), true);
    
    // call on request changes state
    xhttpAcc.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {

            var responseText = this.responseText;
            
         ////console.log("getUser gotten");
            
         //console.log("executeChangePostVis: " + responseText);
            
            //setCookie("codeUser", response, 999999);
            
            if (responseText == 1) {
                //session = responseText;
                
                //setCookie("session", responseText, 1);
                
                //alert("You have been logged in!");
                
                //showLoggedIn();
                
                //checkSession(false);
                
                alert("Your post visibility has been changed!");
                
                //document.getElementById("img" + postToFlag).style.display = "none";
                //document.getElementById("cap" + postToFlag).style.display = "none";
                //document.getElementById("profile" + postToFlag).style.display = "none";
                
                //deletedPosts.push(postToFlag);
                
                // will become issue if user ever makes over 1000 posts, just don't store in html use var
                
                //document.getElementById("postCount").innerHTML--;
                
                sendUpdate("Whoops! " + document.getElementById("name").innerHTML + " just deleted a post! They've now made " + document.getElementById("postCount").innerHTML + " posts");
                
                //checkSession(false);
                hideWindows();
                
            } else {
                alert("Uh oh that didn't work, please reload the page and try again");
            }

            //document.getElementById("deleteSlider").value = 2;
            //setCSSPropertyAccount();

            //likedPosts = JSON.parse(responseText);
            
            //printSession(responseText);
            
            //setCookie("session", responseText, 1);
            
        }
    };

    xhttpAcc.send(formDataAcc);
}

var liking = false;

function updateUserLikesDisplay() {
    
   //console.log("updating user likes!");
    
    //if (session !== "") {
        //getUserLikes(session);
    
        for (var c=0; c < counter; c++) {
            
            var Id = imageArray[c].id;
            
           //console.log("Id: " + Id);
           //console.log("I: " + c);
            //console.log("liked posts: " + JSON.stringify(likedPosts));
            
            if (imageArray[c].hidden) {
                
               //console.log("hidden Post");
                
            } else {
            
                var heart = document.getElementById("heart" + Id); //<i class="fa-solid fa-heart"></i>
                
                if (likedPosts.includes(Id) && heart.classList !== null && heart.classList !== undefined) {
                    if (heart.classList.contains("fa-regular")) {
                        heart.classList.remove("fa-regular");
                        heart.classList.add("fa-solid");
                    }   
                } else {
                    if (heart.classList.contains("fa-solid")) {
                        heart.classList.remove("fa-solid");
                        heart.classList.add("fa-regular");
                    }
                }
                
                var flag = document.getElementById("flag" + Id); //<i class="fa-solid fa-heart"></i>
                
                if (imageArray[c].user == userId) {
                    if (flag.classList.contains("fa-flag")) {
                        flag.classList.remove("fa-flag");
                    }
                    flag.classList.add("fa-eye-slash");
                } else {
                    if (flag.classList.contains("fa-eye-slash")) {
                        flag.classList.remove("fa-eye-slash");
                    }
                    flag.classList.add("fa-flag");
                }
                
                document.getElementById("likes" + Id).innerHTML = displayNum(imageArray[c].likes, false);
            
            }
        
        }
    
    //}
    
}

function likePost(postID) {
    
    if (session !== "") {
    
        if (!liking) {
        
       //console.log("liking #: " + postID);
        
        liking = true;
    
        if (!likedPosts.includes(postID)) {
    
           //console.log("Liking Post! working");
            var formData = new FormData();
            formData.append("id", postID);
            formData.append("session", session);
    
            var xhttp = new XMLHttpRequest();
    
            // Set POST method and ajax file path
            xhttp.open("POST", "./php/likePost.php?_='" + Date.now(), true);
    
            // call on request changes state
            xhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
    
                    var response = this.responseText;
                    
                   //console.log("response is: " + response);
                    
                    if (response == 1) {
                        
                      //console.log("Post Liked");
                        
                        updateLikes(postID);
                        
                        likedPosts.push(postID);
                        
                        likeCount++;
                        
                        document.getElementById("likeCount").innerHTML = displayNum(likeCount, false);
                        
                        sendUpdate("Nice! " + document.getElementById("name").innerHTML + " just liked a post! It was the " + ordinal_suffix_of(parseInt(document.getElementById("likeCount").innerHTML)) + " post that they've liked");
                        
                        //setCookie("likedPosts", JSON.stringify(likedPosts), 999999);
                        //console.log("get cookie " + getCookie(likedPosts));
                        //storeUserLikes(JSON.stringify(likedPosts));
                        
                        liking = false;
                        
                        // for (var i=0; i < imageArray.length; i++) {
                        //     if (imageArray[i].id == postID) {
                        //         // imageArray[i].likes++;
                        //        //console.log("adding like");
                        //     }
                        // }
                        
                        // updateUserLikesDisplay();
                        
                    } else if (response == "emailNotConfirmed") {
                       //console.log("Like Failed");
                        alert("Uh oh! You need to confirm your email before you can like posts");
                        
                        liking = false;
                    } else {
                        liking = false;
                        alert("Uh oh! That didn't work, please try again later");
                    }
                }
            };
    
            xhttp.send(formData);
            
        } else {
           //console.log("unliking the post");
            
            unlikePost(postID);
        }
        
        }
    
    } else {
        alert("Uh oh! It looks like you aren't logged in.");
        makeFormLogin();
        showLogin();
    }
}

function unlikePost(postID) {
    
    if (session !== "") {
            
        //liking = true;
        
       //console.log("unliking #: " + postID);
    
        if (likedPosts.includes(postID)) {
    
           //console.log("Unliking post! working");
            var formData = new FormData();
            formData.append("id", postID);
            formData.append("session", session);
    
            var xhttp = new XMLHttpRequest();
    
            // Set POST method and ajax file path
            xhttp.open("POST", "./php/unlikePost.php?_='" + Date.now(), true);
    
            // call on request changes state
            xhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
    
                    var response = this.responseText;
                    
                    if (response == 1) {
                      //console.log("Post Unliked");
                       
                        updateLikesMinus(postID);
                        
                        for (var i = 0; i < likedPosts.length; i++) { 
            
                            if ( likedPosts[i] === postID) {
                                likedPosts.splice(i, 1); 
                                i--; 
                            }
                        }
                        
                        likeCount--;
                        
                        document.getElementById("likeCount").innerHTML = displayNum(likeCount, false);
                        
                        sendUpdate("Woah! " + document.getElementById("name").innerHTML + " just unliked a post! They have now liked " + document.getElementById("likeCount").innerHTML + " posts");
                        
                        //setCookie("likedPosts", JSON.stringify(likedPosts), 999999);
                        
                        //storeUserLikes(JSON.stringify(likedPosts));
                        
                        //console.log("get cookie " + getCookie(likedPosts));
                        
                        liking = false;
                        
                        // for (var i=0; i < imageArray.length; i++) {
                        //     if (imageArray[i].id == postID) imageArray[i].likes--;
                        // }
                        
                        // updateUserLikesDisplay();
                        
                    } else if (response == "emailNotConfirmed") {
                       //console.log("Like Failed");
                        alert("Uh oh! You need to confirm your email before you can like posts");
                        
                        liking = false;
                    } else {
                        liking = false;
                        alert("Uh oh! That didn't work, please try again later");
                    }
                }
            };
    
            xhttp.send(formData);
            
        }
    
    } else {
        alert("Uh oh! It looks like you aren't logged in.");
        makeFormLogin();
        showLogin();
    }
}

function storeUserLikes(likedPosts) {
    var formData = new FormData();
    formData.append("likes", likedPosts);
    formData.append("session", session);

    var xhttp = new XMLHttpRequest();

    // Set POST method and ajax file path
    xhttp.open("POST", "./php/updateUserLikes.php?_='" + Date.now(), true);

    // call on request changes state
    xhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {

            var response = this.responseText;
            
            setCookie("codeUser", response, 999999);
            
           //console.log("code is " + response);
            
        }
    };

    xhttp.send(formData);
}

function updateLikes(postID) {
    var likePostCount = 0;
    
    for (var i=0; i < imageArray.length; i++) {
        if (imageArray[i].id == postID) {
            imageArray[i].likes++;
            likePostCount = imageArray[i].likes;
        }
    }
    
    document.getElementById("likes" + postID).innerHTML = displayNum(likePostCount, false);
    
    var heart = document.getElementById("heart" + postID); //<i class="fa-solid fa-heart"></i>
    
    if (heart.classList.contains("fa-regular")) {
        heart.classList.remove("fa-regular");
        heart.classList.add("fa-solid");
    }
}

function updateLikesMinus(postID) {
    var likePostCount = 0;
    
    for (var i=0; i < imageArray.length; i++) {
        if (imageArray[i].id == postID) {
            imageArray[i].likes--;
            likePostCount = imageArray[i].likes;
        }
    }
    
    document.getElementById("likes" + postID).innerHTML = displayNum(likePostCount, false);
    
    var heart = document.getElementById("heart" + postID); //<i class="fa-solid fa-heart"></i>
    
    if (heart.classList.contains("fa-solid")) {
        heart.classList.remove("fa-solid");
        heart.classList.add("fa-regular");
    }
}

//window.onscroll = function() {myFunction()};
function scrollHeader() {
  var header = document.getElementById("header");
  var footer = document.getElementById("footer");
  var sticky = header.offsetTop;
  
  //console.log("sticky: " + sticky);
  //console.log("window.pageYOffset: " + window.pageYOffset);
    
  if (window.pageYOffset > sticky) {
    header.classList.add("sticky");
  } else {
    header.classList.remove("sticky");
  }
  
  //if (Math.round(window.innerHeight + window.pageYOffset + 150) < Math.round(document.body.offsetHeight) {
  //  footer.classList.add("stickyFoot");
  //} else {
  //footer.classList.remove("stickyFoot");
  //}
}

const inputElement = document.getElementById("mileRange");
let isChanging = false;

const setCSSProperty = () => {
  const percent =
    ((inputElement.value - inputElement.min) /
    (inputElement.max - inputElement.min)) *
    100;
  // Here comes the magic
  inputElement.style.setProperty("--webkitProgressPercent", `${percent}%`);
}

// Set event listeners
const handleMove = () => {
  if (!isChanging) return;
  setCSSProperty();
};
const handleUpAndLeave = () => isChanging = false;
const handleDown = () => isChanging = true;

inputElement.addEventListener("mousemove", handleMove);
inputElement.addEventListener("mousedown", handleDown);
inputElement.addEventListener("mouseup", handleUpAndLeave);
inputElement.addEventListener("mouseleave", handleUpAndLeave);
inputElement.addEventListener("click", setCSSProperty);

// Init input
setCSSProperty();

function initDateFilters() {
    var dateObjMax = document.getElementById("dateEnd");
    var dateObjMin = document.getElementById("dateStart");
    
    var minDate = new Date(timeMin * 1000);
    var dd = minDate.getDate();
    var mm = minDate.getMonth()+1; //January is 0 so need to add 1 to make it 1!
    var yyyy = minDate.getFullYear();
    if(dd<10){
      dd='0'+dd;
    } 
    if(mm<10){
      mm='0'+mm;
    } 

    minDate = yyyy+'-'+mm+'-'+dd;
    
    //console.log("min date: " + minDate);
    
    dateObjMin.min = minDate;
    dateObjMax.valueAsDate = new Date();
    dateObjMin.value = minDate;
    dateObjMax.max = dateObjMax.value;
    dateObjMax.min = dateObjMin.value;
    dateObjMin.max = dateObjMax.value;

}

initDateFilters();

function updateDates() {
    var dateObjMax = document.getElementById("dateEnd");
    var dateObjMin = document.getElementById("dateStart");
    var minDate = new Date(timeMin*1000);
    
    var dd = minDate.getDate();
    var mm = minDate.getMonth()+1; //January is 0 so need to add 1 to make it 1!
    var yyyy = minDate.getFullYear();
    if(dd<10){
      dd='0'+dd;
    } 
    if(mm<10){
      mm='0'+mm;
    } 

    minDate = yyyy+'-'+mm+'-'+dd;
    //console.log("min date: " + minDate);
    
    dateObjMin.min = minDate;
    dateObjMax.min = dateObjMin.value;
    dateObjMin.max = dateObjMax.value;
    
   //console.log("max dist is " + maxDist);
    
    document.getElementById("mileRange").max = maxDist * 5280;
    document.getElementById("mileRange").value = maxDist * 5280;
    
    setCSSProperty();
    
    filterPosts();
}

function setCookie(cname, cvalue, exdays) {
  const d = new Date();
  d.setTime(d.getTime() + (exdays*24*60*60*1000));
  let expires = "expires="+ d.toUTCString();
  document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

function getCookie(cname) {
  let name = cname + "=";
  let decodedCookie = decodeURIComponent(document.cookie);
  let ca = decodedCookie.split(';');
  for(let i = 0; i <ca.length; i++) {
    let c = ca[i];
    while (c.charAt(0) == ' ') {
      c = c.substring(1);
    }
    if (c.indexOf(name) == 0) {
      return c.substring(name.length, c.length);
    }
  }
  return "";
}

var profileShouldScroll = false;

function closeProfile() {
    document.getElementById("profileDisplay").style.display = "none";
    profileFilter = -1;
    
    filterPosts();
    
    //document.getElementById("feed").style.paddingLeft = "0px";
    //document.getElementById("feed").style.paddingRight = "0px";
    //document.getElementById("feed").style.paddingTop = "0px";//0vw 1.5vw 0vw 1.5vw;
    document.getElementById("feed").style.padding = "0vw";
    //window.scrollTo(0, scrollTop);
    // document.documentElement.scrollTop = scrollTopVar/4;
    // setTimeout(scroll3, 10);
    // setTimeout(scroll2, 10);
    // setTimeout(scroll1, 10);
    
    //make algorithm to scroll until id is displayed
    //fillpics until id is reached and then scroll to bottom
    
    // postIdDisplayed = false;
    
    // while (postIdDisplayed && maxNotHit) {
    //     fillPics(imagesToDisplay);
    // }
    
    ////console.log("postIdDisplayed " + postIdDisplayed);
    
    ////console.log("profilePostId " + profilePostId);
    
    // window.scrollTo(0, document.body.scrollHeight);
    
    profilePostId = -1;
    
   //console.log("Scroll height: " + document.body.scrollHeight);
    
    // if (profileShouldScroll) {
        document.documentElement.scrollTop = scrollTopVar;
    // }
    
}

function scroll3() {
    document.documentElement.scrollTop = scrollTopVar/3;
}

function scroll2() {
    document.documentElement.scrollTop = scrollTopVar/2;
}

function scroll1() {
    document.documentElement.scrollTop = scrollTopVar;
}

function scrollTo(scroll) {
    
    if (document.documentElement.scrollTop < (scroll - 1)) {
        document.documentElement.scrollTop = document.documentElement.scrollTop + 200;
        
        setTimeout(scrollTo(scroll), 1000);
    } else {
        document.documentElement.scrollTop = scroll;
       //console.log("done");
    }
}

var scrollTopVar = 0;

var profilePostId = -1;

var postIdDisplayed = false;

// var showProfileUserName = "";

function showProfile(id, postId) {
    
    profilePostId = postId;
    
    scrollTopVar = document.documentElement.scrollTop;
    
    document.getElementById("profileDisplay").style.display = "block";
    
    profileFilter = id;
    
   //console.log("only showing posts by: " + id);
    
    filterPosts();
    
    var uname = arrayOfPosts[0].postedBy;
    
    var postCount = 0;
    
    for (var c=0; c < imageArray.length; c++) {
        if (imageArray[c].hidden != true && imageArray[c].user == id) postCount++;
    }
    
    if (id == userId) {
        document.getElementById("thisUser").innerHTML = "You have made ";
        document.getElementById("thisUser2").innerHTML = "You have made ";
        document.getElementById("actionButtons").style.display = "none";
        document.getElementById("actionButtons2").style.display = "none";
    } else {
        document.getElementById("thisUser").innerHTML = "This user has made ";
        document.getElementById("thisUser2").innerHTML = "This user has made ";
        document.getElementById("actionButtons").style.display = "block";
        document.getElementById("actionButtons2").style.display = "block";
    }
    document.getElementById("profilePostCount").innerHTML = displayNum(arrayOfPosts.length, false);
    document.getElementById("profilePostCount2").innerHTML = displayNum(postCount, false);
    document.getElementById("feed").style.padding = "2vw";
    
    if (userIsBlocked(id)) {
        document.getElementById("blockButtonProfile").innerHTML = "Unblock";
        document.getElementById("blockButtonProfile2").innerHTML = "Unblock";
        document.getElementById("blockButtonProfile").setAttribute("onclick", "unblockUser("+ id + ")");
    } else {
        document.getElementById("blockButtonProfile").setAttribute("onclick", "blockUser('"+ uname + "')");
        document.getElementById("blockButtonProfile").innerHTML = "Block";
        document.getElementById("blockButtonProfile2").innerHTML = "Block";
    }
    if (userIsFollowed(id)) {
        document.getElementById("followButtonProfile").innerHTML = "Unfollow";
        document.getElementById("followButtonProfile2").innerHTML = "Unfollow";
        document.getElementById("followButtonProfile").setAttribute("onclick", "unfollowUser("+ id + ")");
    } else {
        document.getElementById("followButtonProfile").innerHTML = "Follow";
        document.getElementById("followButtonProfile2").innerHTML = "Follow";
        document.getElementById("followButtonProfile").setAttribute("onclick", "followUser('"+ uname + "')");
    }
    
    if (uname == "Noah" || uname == "1") uname = uname + ' <i class="fa-solid fa-circle-check"></i>';
    
    document.getElementById("userNameProfile").innerHTML = uname;
    document.getElementById("userNameProfile2").innerHTML = uname;
    
    var profileDisplays = document.getElementsByClassName("userText");
    
    for (var x=0; x < profileDisplays.length; x++) {
        profileDisplays[x].style.display = "none";
    }
    
    scrollTop = document.documentElement.scrollTop;
    
    if (scrollTop < window.pageYOffset) scrollTop = window.pageYOffset;
    
   //console.log("Scroll top is: " + scrollTop);
    
    window.scrollTo(0, 0);
}

function closeProfileMy() {
    
    document.getElementById("profileDisplayMy").style.display = "none";
    
    profileFilter = -1;
    
    document.getElementById("feed").innerHTML = "";
    
    imagesToDisplay = [];
    
    showPosts();
    
    document.getElementById("feed").style.padding = "0vw";
    
    profilePostId = -1;
}

function showMyPosts() {
    
    document.getElementById("profileDisplayMy").style.display = "block";
    
    profileFilter = userId;
    
    showPostsByMe();
    
    // var uname = arrayOfPosts[0].postedBy;
    var uname = uName;
                    
    console.log("Uname is: " + uname);
    
    console.log("ligma cock");
    
    // for (var c=0; c < imageArray.length; c++) {
    //     if (imageArray[c].hidden != true && imageArray[c].postedBy == uname) postCount++;
    // }
    
    document.getElementById("thisUserMy").innerHTML = "You have made ";
    document.getElementById("thisUser2My").innerHTML = "You have made ";
    
    document.getElementById("profilePostCountMy").innerHTML = displayNum(postCount, false);
    document.getElementById("profilePostCount2My").innerHTML = displayNum(postCount, false);
    
    document.getElementById("feed").style.padding = "2vw";
    
    if (uname == "Noah") uname = uname + ' <i class="fa-solid fa-circle-check"></i>';
    
    document.getElementById("userNameProfileMy").innerHTML = uname;
    document.getElementById("userNameProfile2My").innerHTML = uname;
    
    var profileDisplays = document.getElementsByClassName("userText");
    
    for (var x=0; x < profileDisplays.length; x++) {
        profileDisplays[x].style.display = "none";
    }
    
    window.scrollTo(0, 0);
}

function getUsersBlockedMe() {
        
    //console.log("testing account creation");
    
    var formDataAcc = new FormData();
    formDataAcc.append("session", session);//passNewName

    var xhttpAcc = new XMLHttpRequest();

    // Set POST method and ajax file path
    xhttpAcc.open("POST", "./php/getUsersBlockedMe.php?_='" + Date.now(), true);
    
    // call on request changes state
    xhttpAcc.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {

            var responseText = this.responseText;
            
         ////console.log("getUser gotten");
            
         //console.log("users blocked me: " + responseText);
            
            //setCookie("codeUser", response, 999999);
            
            if (responseText.charAt(0) == '[') {

                usersBlockedMe = JSON.parse(responseText);
                
            } else {
                usersBlockedMe = [];
            }

            //likedPosts = JSON.parse(responseText);
            
            //printSession(responseText);
            
            //setCookie("session", responseText, 1);
            
        }
    };

    xhttpAcc.send(formDataAcc);
}

function getFollowedLiked() {
            
    //console.log("testing account creation");
    
    var formDataAcc = new FormData();
    formDataAcc.append("session", session);//passNewName

    var xhttpAcc = new XMLHttpRequest();

    // Set POST method and ajax file path
    xhttpAcc.open("POST", "./php/getFollowedLiked.php?_='" + Date.now(), true);
    
    // call on request changes state
    xhttpAcc.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {

            var responseText = this.responseText;
            
         ////console.log("getUser gotten");
            
         //console.log("users blocked me: " + responseText);
            
            //setCookie("codeUser", response, 999999);
            
            if (responseText.charAt(0) == '[') {

                followLiked = JSON.parse(responseText);
                
            } else {
                followLiked = [];
            }

            //likedPosts = JSON.parse(responseText);
            
            //printSession(responseText);
            
            //setCookie("session", responseText, 1);
            
        }
    };

    xhttpAcc.send(formDataAcc);
}

function getFollowedMe() {
            
    //console.log("testing account creation");
    
    var formDataAcc = new FormData();
    formDataAcc.append("session", session);//passNewName

    var xhttpAcc = new XMLHttpRequest();

    // Set POST method and ajax file path
    xhttpAcc.open("POST", "./php/getFollowedMe.php?_='" + Date.now(), true);
    
    // call on request changes state
    xhttpAcc.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {

            var responseText = this.responseText;
            
         ////console.log("getUser gotten");
            
         //console.log("users blocked me: " + responseText);
            
            //setCookie("codeUser", response, 999999);
            
            if (responseText.charAt(0) == '[') {

                usersFollowingMe = JSON.parse(responseText);
                
            } else {
                usersFollowingMe = [];
            }

            //likedPosts = JSON.parse(responseText);
            
            //printSession(responseText);
            
            //setCookie("session", responseText, 1);
            
        }
    };

    xhttpAcc.send(formDataAcc);
}