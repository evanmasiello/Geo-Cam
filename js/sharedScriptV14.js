const queryString = window.location.search;
const urlParams = new URLSearchParams(queryString);

var postMode = "norm";

const inputElementAccount = document.getElementById("deleteSlider");
let isChangingAccount = false;

const setCSSPropertyAccount = () => {
  const percentAccount =
    ((inputElementAccount.value - inputElementAccount.min) /
    (inputElementAccount.max - inputElementAccount.min)) *
    100;
  // Here comes the magics
  inputElementAccount.style.setProperty("--webkitProgressPercent", `${percentAccount}%`);
}

// Set event listeners
const handleMoveAccount = () => {
  if (!isChangingAccount) return;
  setCSSPropertyAccount();
};
const handleUpAndLeaveAccount = () => isChangingAccount = false;
const handleDownAccount = () => isChangingAccount = true;

inputElementAccount.addEventListener("mousemove", handleMoveAccount);
inputElementAccount.addEventListener("mousedown", handleDownAccount);
inputElementAccount.addEventListener("mouseup", handleUpAndLeaveAccount);
inputElementAccount.addEventListener("mouseleave", handleUpAndLeaveAccount);
inputElementAccount.addEventListener("click", setCSSPropertyAccount);

// Init input
setCSSPropertyAccount();

function tryToDelete(val) {
    setCSSPropertyAccount();
    
    if (val >= 100) {
        execDeleteAccount();
    }
}

var mobileUser; 

if(/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
    mobileUser = true;
} else {
    mobileUser = false;
}

var isInIframe = false;

if ( window.location !== window.parent.location ) {
  isInIframe = true;
} else {
  isInIframe = false;
}

var userId = -1;

var allUsers = [];

var standalone = window.navigator.standalone,
  userAgent = window.navigator.userAgent.toLowerCase(),
  safari = /safari/.test(userAgent),
  ios = /iphone|ipod|ipad/.test(userAgent);

var isInApp = false;

if (ios) {
  if (!standalone && safari) {
    // Safari
    isInApp = false;
  } else if (!standalone && !safari) {
    // iOS webview
    isInApp = true;
  }
} else {
  if (userAgent.includes('wv')) {
    // Android webview
    isInApp = true;
  } else {
    // Chrome
    isInApp = false;
  }
}

//if (isInApp) alert("you are in the app!");

var srcUrlParam = urlParams.get("mode");
var srcCookie = getCookie("mode");

var shouldBePc = window.innerHeight < window.innerWidth && urlParams.get("src") != "pcPage" && !mobileUser && srcCookie != "mobile" && srcUrlParam != "mobile" && !isInIframe;

var hasVisited = getCookie("hasVisited");
var session = getCookie("session");

if (shouldBePc) {
    var path = window.location.pathname;
    var page = path.split("/").pop();
    
    window.location.href = 'pc?site=' + page;
} else {
    if (session === "" && hasVisited === "") {
        document.getElementById("welcomeDiv").style.display = "block";
        makeFormCreate();
        setCookie("hasVisited", 1, 9999);
    } else {
        makeFormLogin();
        //navigator.geolocation.getCurrentPosition(successInit, error);
        setCookie("hasVisited", 1, 9999);
    }
}

if (urlParams.get("src") == "pcPage") {
    
    console.log("updating links");
    
    var linkElements = document.getElementsByClassName("footLink");
    
    console.log("link elements length: " + linkElements.length);
    
    for (var x=0; x < linkElements.length; x++) {
        console.log("old link: " + linkElements[x].href);
        
        linkElements[x].href = linkElements[x].href + "?src=pcPage";
        
        console.log("new link: " + linkElements[x].href + "?src=pcPage");
    }
}

window.onbeforeunload = function(e) {
  if (urlParams.get("mode") == "mobile") setCookie("mode", "mobile", 0.00138889);
};

var long = null;
var lat = null;
var oldLong;
var oldLat;

if (session !== "") {
    checkSession();
    showLoggedIn();
} else {
    if (long === null && lat === null && hasVisited !== "") navigator.geolocation.getCurrentPosition(successInit, error);
    
    var xhttpUsers = new XMLHttpRequest();
        var formDataUsers = new FormData();
        formDataUsers.append("key", "yungleandoer2002");
    
        // Set POST method and ajax file path
        xhttpUsers.open("POST", "./php/getUsers.php?_='" + Date.now(), true);
        
        // call on request changes state
        xhttpUsers.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
    
                var responseTextUsers = this.responseText;

                //console.log("response text users: " + responseTextUsers);
                
                if (responseTextUsers != "noKey") {
                    console.log("users worked!");
                    allUsers = JSON.parse(responseTextUsers);
                } else {
                    allUsers = [];
                }
        }
        };
        
        xhttpUsers.send(formDataUsers);
}

function hideWelcome() {
    if (long === null || lat === null) navigator.geolocation.getCurrentPosition(successInit, error);
    
    hideWindows();
    
}

function error() {
    try {
        document.getElementById("feed").innerHTML = "<h2 class='contentText'>Oh no we can't show you posts because we don't know where you are! Please allow location access and reload the page</h2>";   
    } catch(err) {
        
    }
}

function showInfo() {
    if (document.getElementById("editInfo").style.display == "none") {
        document.getElementById("editInfo").style.display = "block";
        document.getElementById("showInfoButton").innerHTML = "Hide Edit Info";
    } else {
        document.getElementById("editInfo").style.display = "none";
        document.getElementById("showInfoButton").innerHTML = "Edit Info";
    }
}

function showWelcome() {
    document.getElementById("welcomeDiv").style.display = "block";
}

var time = Date.now();

var access = getCookie("access");

console.log("time is: " + time);

function ordinal_suffix_of(i) {
    var j = i % 10,
        k = i % 100;
    if (j == 1 && k != 11) {
        return i + "st";
    }
    if (j == 2 && k != 12) {
        return i + "nd";
    }
    if (j == 3 && k != 13) {
        return i + "rd";
    }
    return i + "th";
}

var path = window.location.pathname;
var page = path.split("/").pop();

console.log("time is: " + time);
console.log("should be less than: " + 1655294404448);

if (time < 1655294404448 && access != "1" && urlParams.get('access') != 1 && urlParams.get('access') != "1" ) {
    if (page == "camera") {
        window.location.replace("unavailable?site=" + "camera");     
    } else {
        window.location.replace("unavailable?site=" + "index");
    }
}

console.log("session cookie: " + session);

//var showingAbout = false;

function showAbout() {
    if (document.getElementById("aboutDiv").style.display == "block") {
        document.getElementById("aboutDiv").style.display = "none";
        //showingAbout = false;
    } else {
        hideWindows();
        document.getElementById("aboutDiv").style.display = "block";
        //showingAbout = true;
    }
}

function sendUpdate(msg) {
    var url = "https://discord.com/api/webhooks/988448246491914250/VEX49QCLn7jHabWDmExWFpGUTCcvjqd3Tc4OoveCvHEUQKLdYU9_6Ad8sqvdWxff-WVw";
    
    var message = {
        "content": msg
    }
    
    //fetch(url, {"method":"POST", "headers": {"content-type": "application/json"}, "body": JSON.stringify(message)});
}

function forgotPassword() {
        
    //console.log("testing account creation");
    
    var formDataAcc = new FormData();
    //formDataAcc.append("uname", document.getElementById("unameForgot").value);
    formDataAcc.append("email", document.getElementById("emailForgot").value);

    var xhttpAcc = new XMLHttpRequest();

    // Set POST method and ajax file path
    xhttpAcc.open("POST", "./php/forgotPassword.php?_='" + Date.now(), true);
    
    // call on request changes state
    xhttpAcc.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {

            var responseText = this.responseText;
            
          //console.log("getUser gotten");
            
          console.log("session: " + responseText);
            
            if (responseText == 1) {
                alert("The email has been sent");
            } else if (responseText == "badLogin") {
                alert("Uh oh! It looks like your username or email are incorrect");
            } else {
                alert("Uh oh that didn't work! Please try again later");
            }
            
        }
    };

    xhttpAcc.send(formDataAcc);
}

var uName = "";

var postCount = 0;
var likeCount = 0;
var commentCount = 0;

var currUserId = -1;

function checkSession() {
    
    if (session !== "") {
        var formDataAcc = new FormData();
        formDataAcc.append("session", session);
    
        var xhttpAcc = new XMLHttpRequest();
    
        // Set POST method and ajax file path
        xhttpAcc.open("POST", "./php/checkSession.php?_='" + Date.now(), true);
        
        // call on request changes state
        xhttpAcc.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
    
                var responseText = this.responseText;
                
              //console.log("getUser gotten");
                
              //console.log("session: " + responseText);
                
                //setCookie("codeUser", response, 999999);
                
                if (responseText !== "invalidSession") {
                    //session = responseText;
                    
                    //alert("You have been logged in!");
                    console.log("session verified");
                    console.log("response text: " + responseText);
                    
                    setCookie("session", session, 30);
                    
                    var userObj = JSON.parse(responseText);
                    
                    userId = userObj.id;
                    
                    if (userObj.followed !== undefined) {
                        followedUsers = JSON.parse(userObj.followed);
                    } else {
                        followedUsers = [];
                    }
                    
                    if (userObj.blocked !== undefined) {
                        blockedUsers = JSON.parse(userObj.blocked);
                    } else {
                        blockedUsers = [];
                    }
                    
                    uName = userObj.user;
                    
                    currUserId = userObj.id;
                    
                        if (uName == "appTest") {
                            //lat = 40.634311;
                            //long = -74.908772;
                        }
                        
                    var commCount = 0;
                    
                    if (userObj.commentCount != null && userObj.commentCount != undefined) commCount = userObj.commentCount;
                    
                    updateLoginInfo(userObj.user, userObj.postCount, JSON.parse(userObj.likes).length, commCount, userObj.emailIsVerified);
                    
                    try {
                        document.getElementById("userNameDisplay").innerHTML = uName;
                        
                        if (uName == "Noah") document.getElementById("userNameDisplay").innerHTML = uName + ' <i class="fa-solid fa-circle-check"></i>';
                    } catch(error) {
                        console.log(error);
                    }
                    
                    try {
                        refreshComments();
                    } catch(error) {
                        console.log(error);
                    }
                    
                    try {
                        
                        console.log("in try");
                        
                        var postMode = "norm";
                        
                        likedPosts = JSON.parse(userObj.likes);
                        
                        if (userId == 1 || userId == 4) {
                            console.log("in if for name");
                            postMode = "admin";
                            document.getElementById("switchPostMode").style.display = "block";
                        } else {
                            document.getElementById("switchPostMode").style.display = "none";
                        }
                        
                    }
                    catch(error) {
                        console.log(error);
                    }
                    try {
                        updateUserLikesDisplay();
                        if (postsToShow != "all" && lat !== null && long !== null) sortImages();
                    }
                    catch(error) {
                        console.log(error);
                    }
                    
                } else {
                    //alert("Uh oh! it looks like you've been logged out");
                    
                    logOut();
                    
                    try {
                        // document.getElementById("userNameDisplay").innerHTML = "name";
                    } catch(error) {
                        console.log(error);
                    }
                }
                
                if (long === null && lat === null && hasVisited !== "") navigator.geolocation.getCurrentPosition(successInit, error);
    
                //likedPosts = JSON.parse(responseText);
                
                //printSession(responseText);
                
                //setCookie("session", responseText, 1);
                
            }
        };
    
        xhttpAcc.send(formDataAcc);
    } else {
        if (long === null && lat === null && hasVisited !== "") navigator.geolocation.getCurrentPosition(successInit, error);
        try {
                        // document.getElementById("userNameDisplay").innerHTML = "name";
                    } catch(error) {
                        console.log(error);
                    }
    }
    
    // //allUsers
    // console.log("about to do users");
    // console.log("about to do users");
    // console.log("about to do users");
    // console.log("about to do users");
    // console.log("about to do users");
    
        var xhttpUsers = new XMLHttpRequest();
        var formDataUsers = new FormData();
        formDataUsers.append("key", "yungleandoer2002");
    
        // Set POST method and ajax file path
        xhttpUsers.open("POST", "./php/getUsers.php?_='" + Date.now(), true);
        
        // call on request changes state
        xhttpUsers.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
    
                var responseTextUsers = this.responseText;

                //console.log("response text users: " + responseTextUsers);
                
                if (responseTextUsers != "noKey") {
                    console.log("users worked!");
                    allUsers = JSON.parse(responseTextUsers);
                } else {
                    allUsers = [];
                }
        };
        }
        
        xhttpUsers.send(formDataUsers);
}

function updateLoginInfo(name, postCountArg, likeCountArg, commentCountArg, emailIsVerified) {
    
    console.log("name is: " + name + " posts: " + postCount + " likes: " + likeCount);
    
    document.getElementById("name").innerHTML = name;
    
    if (name == "Noah") document.getElementById("name").innerHTML = name + ' <i class="fa-solid fa-circle-check"></i>';
    
    postCount = postCountArg;
    likeCount = likeCountArg;
    commentCount = commentCountArg;
    
    document.getElementById("postCount").innerHTML = displayNum(postCount);
    document.getElementById("likeCount").innerHTML = displayNum(likeCount);//commentCount
    document.getElementById("commentCount").innerHTML = displayNum(commentCount);
    
    if (!emailIsVerified) {
        //do something to let them resend verification
        console.log("email is not gucci"); //emailVerify
        document.getElementById("emailVerify").style.display = "block";
    } else {
        document.getElementById("emailVerify").style.display = "none";
    }
    
    //showLoggedIn();
}

function loginAccount() {
    
    console.log("testing login");
    
    var formDataAcc = new FormData();
    formDataAcc.append("uname", document.getElementById("unameLogin").value);
    formDataAcc.append("pass", document.getElementById("passLogin").value);

    var xhttpAcc = new XMLHttpRequest();

    // Set POST method and ajax file path
    xhttpAcc.open("POST", "./php/signIn.php?_='" + Date.now(), true);
    
    // call on request changes state
    xhttpAcc.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {

            var responseText = this.responseText;
            
          console.log("getUser gotten");
            
          console.log("session: " + responseText);
            
            //setCookie("codeUser", response, 999999);
            
            if (responseText !== "") {
                session = responseText;
                
                setCookie("session", responseText, 30);
                
                //alert("You have been logged in!");
                
                showLoggedIn();
                
                checkSession();
                
                try {
                    //updateUserLikesDisplay();
                    filterPosts();
                }
                catch(err) {
                    console.log("err " + err);
                }

                
            } else {
                alert("Uh oh that didn't work, make sure you enter the right password!");
            }

            //likedPosts = JSON.parse(responseText);
            
            //printSession(responseText);
            
            setCookie("session", responseText, 30);
            
        }
    };

    xhttpAcc.send(formDataAcc);
}

function resendEmailVerify() {
    
    //console.log("testing account creation");
    
    var formDataAcc = new FormData();
    formDataAcc.append("session", session);

    var xhttpAcc = new XMLHttpRequest();

    // Set POST method and ajax file path
    xhttpAcc.open("POST", "./php/resendEmailVerify.php?_='" + Date.now(), true);
    
    // call on request changes state
    xhttpAcc.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {

            var responseText = this.responseText;
            
         // console.log("getUser gotten");
            
          console.log("resendEmailVerify: " + responseText);
            
            //setCookie("codeUser", response, 999999);
            
            if (responseText == 1) {
                //session = responseText;
                
                //setCookie("session", responseText, 1);
                
                //alert("You have been logged in!");
                
                //showLoggedIn();
                
                //checkSession();
                
                alert("The email has been sent!");

                
            } else {
                alert("Uh oh that didn't work, please try again later");
            }

            //likedPosts = JSON.parse(responseText);
            
            //printSession(responseText);
            
            //setCookie("session", responseText, 1);
            
        }
    };

    xhttpAcc.send(formDataAcc);
}

function execDeleteAccount() {
    
    //console.log("testing account creation");
    
    var formDataAcc = new FormData();
    formDataAcc.append("session", session);
    formDataAcc.append("pass", document.getElementById("passDelete").value);

    var xhttpAcc = new XMLHttpRequest();

    // Set POST method and ajax file path
    xhttpAcc.open("POST", "./php/deleteUser.php?_='" + Date.now(), true);
    
    // call on request changes state
    xhttpAcc.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {

            var responseText = this.responseText;
            
         // console.log("getUser gotten");
            
          console.log("execDeleteAccount: " + responseText);
            
            //setCookie("codeUser", response, 999999);
            
            if (responseText == 1) {
                //session = responseText;
                
                //setCookie("session", responseText, 1);
                
                //alert("You have been logged in!");
                
                //showLoggedIn();
                
                //checkSession();
                
                alert("Your account has been deleted!");
                
                try {
                    for (var x=0; x < imageArray.length; x++) {
                        if (imageArray[x].user == userId) { 
                        
                            deletedPosts.push(imageArray[x].id);
                        
                            document.getElementById("img" + imageArray[x].id).style.display = "none";
                            document.getElementById("cap" + imageArray[x].id).style.display = "none";
                            document.getElementById("profile" + imageArray[x].id).style.display = "none";
                        }
                    }
                } catch(err) {
                    
                }
                
                sendUpdate("Oh no! " + document.getElementById("name").innerHTML + " deleted their account!");
                
                checkSession();
                showLoggedIn();
                
            } else {
                alert("Uh oh that didn't work, please make sure that you entered the right password");
            }

            document.getElementById("deleteSlider").value = 2;
            setCSSPropertyAccount();

            //likedPosts = JSON.parse(responseText);
            
            //printSession(responseText);
            
            //setCookie("session", responseText, 1);
            
        }
    };

    xhttpAcc.send(formDataAcc);
    alert("Your request is being proccessed, this may take a moment");
}

var newName;
var oldName;

function execChangeName() {
    
    //console.log("testing account creation");
    
    var formDataAcc = new FormData();
    formDataAcc.append("session", session);//passNewName
    formDataAcc.append("newName", document.getElementById("unameNew").value);
    formDataAcc.append("pass", document.getElementById("passNewName").value);
    
    newName = document.getElementById("unameNew").value;
    oldName = document.getElementById("name").innerHTML;

    var xhttpAcc = new XMLHttpRequest();

    // Set POST method and ajax file path
    xhttpAcc.open("POST", "./php/execChangeName.php?_='" + Date.now(), true);
    
    // call on request changes state
    xhttpAcc.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {

            var responseText = this.responseText;
            
         // console.log("getUser gotten");
            
          console.log("execChangeName: " + responseText);
            
            //setCookie("codeUser", response, 999999);
            
            if (responseText == 1) {
                //session = responseText;
                
                //setCookie("session", responseText, 1);
                
                //alert("You have been logged in!");
                
                //showLoggedIn();
                
                //checkSession();
                
                alert("Your username has been changed!");
                
                sendUpdate("Look at that! " + oldName + " changed their username to " + newName);
                
                showLoggedIn();
                checkSession();//$containsBlocked
                
            } else if (responseText == "badName") {
                alert("Uh oh! It looks like that name is already being used");
            } else if (responseText == "badNameUsed") {
                alert("Uh oh! It looks like that name is already associated with your account");
            } else if (responseText == "nameTooLong") {
                alert("Uh oh that didn't work! Please make sure your username isn't longer than 15 characters");
            } else if (responseText == "containsBlocked") {
                alert("It looks like there are some vulgar words in your username, please change your name and try again");
            } else if (responseText == "containsBadChars") {
                alert("It looks like your username contains some restricted characters. Please try again and only use letters, numbers, and basic symbols");
            } else {
                alert("Uh oh that didn't work, please make sure that you entered the right password");
            }

            //likedPosts = JSON.parse(responseText);
            
            //printSession(responseText);
            
            //setCookie("session", responseText, 1);
            
        }
    };

    xhttpAcc.send(formDataAcc);
}

function execChangePass() {
    
    //console.log("testing account creation");
    
    var formDataAcc = new FormData();
    formDataAcc.append("session", session);
    formDataAcc.append("passNew", document.getElementById("passNew").value);
    formDataAcc.append("pass", document.getElementById("passOld").value);

    var xhttpAcc = new XMLHttpRequest();

    // Set POST method and ajax file path
    xhttpAcc.open("POST", "./php/execChangePass.php?_='" + Date.now(), true);
    
    // call on request changes state
    xhttpAcc.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {

            var responseText = this.responseText;
            
         // console.log("getUser gotten");
            
          console.log("execChangePass: " + responseText);
            
            //setCookie("codeUser", response, 999999);
            
            if (responseText == 1) {
                //session = responseText;
                
                //setCookie("session", responseText, 1);
                
                //alert("You have been logged in!");
                
                //showLoggedIn();
                
                //checkSession();
                
                alert("Your password has been changed!");
                
                sendUpdate("Look at that! " + document.getElementById("name").innerHTML + " changed their password");
                
                showLoggedIn();

            } else if (responseText == "badPass") {
                alert("Uh oh that didn't work, please make sure you old password is correct");
            } else {
                alert("Uh oh that didn't work, please try again later");
            }

            //likedPosts = JSON.parse(responseText);
            
            //printSession(responseText);
            
            //setCookie("session", responseText, 1);
            
        }
    };

    xhttpAcc.send(formDataAcc);
}

function execChangeMail() {
    
    //console.log("testing account creation");
    
    var formDataAcc = new FormData();
    formDataAcc.append("session", session);
    formDataAcc.append("newMail", document.getElementById("mailNew").value);
    formDataAcc.append("pass", document.getElementById("passNewMail").value);

    var xhttpAcc = new XMLHttpRequest();

    // Set POST method and ajax file path
    xhttpAcc.open("POST", "./php/execChangeMail.php?_='" + Date.now(), true);
    
    // call on request changes state
    xhttpAcc.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {

            var responseText = this.responseText;
            
         // console.log("getUser gotten");
            
          console.log("execChangeMail: " + responseText);
            
            //setCookie("codeUser", response, 999999);
            
            if (responseText == 1) {
                //session = responseText;
                
                //setCookie("session", responseText, 1);
                
                //alert("You have been logged in!");
                
                //showLoggedIn();
                
                //checkSession();
                
                alert("Your email has been changed!");
                
                sendUpdate("Look at that! " + document.getElementById("name").innerHTML + " changed their email");
                
                checkSession();
                
                showLoggedIn();
                
            } else if (responseText == "badMail") {
                alert("Uh oh that didn't work, please make sure you enter a valid email");
            } else if (responseText == "mailUsed") {
                alert("Uh oh! It looks like that email is already associated with another account");
            } else if (responseText == "badSession") {
                alert("Uh oh that didn't work, make sure you entered the right password");
            } else if (responseText == "badEmailUsed") {
                alert("Uh oh! It looks like that email is already associated with your account");
            } else {
                alert("Uh oh that didn't work, please try again later");
            }

            //likedPosts = JSON.parse(responseText);
            
            //printSession(responseText);
            
            //setCookie("session", responseText, 1);
            
        }
    };

    xhttpAcc.send(formDataAcc);
}

function createAccount() {
    
    console.log("testing account creation");
    
    var formDataAcc = new FormData();
    formDataAcc.append("uname", document.getElementById("unameCreate").value);
    formDataAcc.append("pass", document.getElementById("passCreate").value);
    formDataAcc.append("email", document.getElementById("emailCreate").value);

    var xhttpAcc = new XMLHttpRequest();

    // Set POST method and ajax file path
    xhttpAcc.open("POST", "./php/createUser.php?_='" + Date.now(), true);
    
    // call on request changes state
    xhttpAcc.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {

            var responseText = this.responseText;
            
          //console.log("getUser gotten");
            
          console.log("session: " + responseText);
            
            if (responseText.length == 64) {
                session = responseText;
                alert("Your account has been created!");
                setCookie("session", responseText, 4);
                showLoggedIn();
                checkSession();
                sendUpdate("Oh yeah! " + document.getElementById("unameCreate").value + " just made their account!");
            } else if (responseText == "badEmail") {
                alert("Uh oh that didn't work! There is already an account associated with that email");
            } else if (responseText == "badName") {
                alert("Uh oh that didn't work! There is already an account with that username");
            } else if (responseText == "mailSend") {
                alert("Uh oh that didn't work! Ensure that you provided a proper email");
            } else if (responseText == "nameTooLong") {
                alert("Uh oh that didn't work! Please make sure your username isn't longer than 15 characters");
            } else if (responseText == "containsBlocked") {
                alert("It looks like there are some vulgar words in your username, please change your name and try again");
            } else if (responseText == "containsBadChars") {
                alert("It looks like your username contains some restricted characters. Please try again and only use letters, numbers, and basic symbols");
            } else {
                alert("Uh oh that didn't work! Please try again later");
            }
            
        }
    };

    xhttpAcc.send(formDataAcc);
}//forgotPass showLogin()

function showForgotPass() {
    
    //showLogin();
    
    document.getElementById("loginFields").style.display = "none";
    document.getElementById("createFields").style.display = "none";
    document.getElementById("loginOptions").style.display = "none";
    document.getElementById("accountView").style.display = "none";
    document.getElementById("forgotPass").style.display = "block";
    document.getElementById("resetPass").style.display = "none";
    document.getElementById("resetMail").style.display = "none";
    document.getElementById("resetName").style.display = "none";
    document.getElementById("deleteAccount").style.display = "none";
    document.getElementById("manageBlocked").style.display = "none";
    document.getElementById("manageFollowed").style.display = "none";
}

function makeFormLogin() {
    document.getElementById("loginFields").style.display = "block";
    document.getElementById("createFields").style.display = "none";
    document.getElementById("loginText").style.textDecoration = "";
    document.getElementById("createText").style.textDecoration = "underline";
    document.getElementById("loginOptions").style.display = "block";
    document.getElementById("accountView").style.display = "none";
    document.getElementById("forgotPass").style.display = "none";
    document.getElementById("resetPass").style.display = "none";
    document.getElementById("resetMail").style.display = "none";
    document.getElementById("resetName").style.display = "none";
    document.getElementById("deleteAccount").style.display = "none";
    document.getElementById("manageBlocked").style.display = "none";
    document.getElementById("manageFollowed").style.display = "none";
}

function makeFormCreate() {
    document.getElementById("loginFields").style.display = "none";
    document.getElementById("createFields").style.display = "block";
    document.getElementById("loginText").style.textDecoration = "underline";
    document.getElementById("createText").style.textDecoration = "";
    document.getElementById("loginOptions").style.display = "block";
    document.getElementById("accountView").style.display = "none";
    document.getElementById("forgotPass").style.display = "none";
    document.getElementById("resetPass").style.display = "none";
    document.getElementById("resetMail").style.display = "none";
    document.getElementById("resetName").style.display = "none";
    document.getElementById("deleteAccount").style.display = "none";
    document.getElementById("manageBlocked").style.display = "none";
    document.getElementById("manageFollowed").style.display = "none";
}

function showLoggedIn() {
    document.getElementById("loginFields").style.display = "none";
    document.getElementById("createFields").style.display = "none";
    document.getElementById("accountView").style.display = "block";
    document.getElementById("loginOptions").style.display = "none";
    document.getElementById("forgotPass").style.display = "none";
    document.getElementById("resetPass").style.display = "none";
    document.getElementById("resetMail").style.display = "none";
    document.getElementById("resetName").style.display = "none";
    document.getElementById("deleteAccount").style.display = "none";
    document.getElementById("manageBlocked").style.display = "none";
    document.getElementById("manageFollowed").style.display = "none";
    
    //getUserLikes();
    
    //try {
        //updateUserLikesDisplay();
        
    //    getUserLikes();
    //}
    //catch(err) {
    //  console.log("erro: " + err);   
    //}
}

function changeName() {
    document.getElementById("loginFields").style.display = "none";
    document.getElementById("createFields").style.display = "none";
    document.getElementById("loginOptions").style.display = "none";
    document.getElementById("accountView").style.display = "none";
    document.getElementById("forgotPass").style.display = "none";
    document.getElementById("resetName").style.display = "block";
    document.getElementById("resetPass").style.display = "none";
    document.getElementById("resetMail").style.display = "none";
    document.getElementById("deleteAccount").style.display = "none";
    document.getElementById("manageBlocked").style.display = "none";
    document.getElementById("manageFollowed").style.display = "none";
}

function changePass() {
    document.getElementById("loginFields").style.display = "none";
    document.getElementById("createFields").style.display = "none";
    document.getElementById("loginOptions").style.display = "none";
    document.getElementById("accountView").style.display = "none";
    document.getElementById("forgotPass").style.display = "none";
    document.getElementById("resetPass").style.display = "block";
    document.getElementById("resetMail").style.display = "none";
    document.getElementById("resetName").style.display = "none";
    document.getElementById("deleteAccount").style.display = "none";
    document.getElementById("manageBlocked").style.display = "none";
    document.getElementById("manageFollowed").style.display = "none";
}

function deleteAccount() {
    document.getElementById("loginFields").style.display = "none";
    document.getElementById("createFields").style.display = "none";
    document.getElementById("loginOptions").style.display = "none";
    document.getElementById("accountView").style.display = "none";
    document.getElementById("forgotPass").style.display = "none";
    document.getElementById("resetPass").style.display = "none";
    document.getElementById("resetMail").style.display = "none";
    document.getElementById("resetName").style.display = "none";
    document.getElementById("deleteAccount").style.display = "block";
    document.getElementById("manageBlocked").style.display = "none";
    document.getElementById("manageFollowed").style.display = "none";
}

function changeEmail() {
    document.getElementById("loginFields").style.display = "none";
    document.getElementById("createFields").style.display = "none";
    document.getElementById("loginOptions").style.display = "none";
    document.getElementById("accountView").style.display = "none";
    document.getElementById("forgotPass").style.display = "none";
    document.getElementById("resetMail").style.display = "block";
    document.getElementById("resetName").style.display = "none";
    document.getElementById("resetPass").style.display = "none";
    document.getElementById("deleteAccount").style.display = "none";
    document.getElementById("manageBlocked").style.display = "none";
    document.getElementById("manageFollowed").style.display = "none";
}

function manageBlocked() {
    document.getElementById("loginFields").style.display = "none";
    document.getElementById("createFields").style.display = "none";
    document.getElementById("loginOptions").style.display = "none";
    document.getElementById("accountView").style.display = "none";
    document.getElementById("forgotPass").style.display = "none";
    document.getElementById("resetMail").style.display = "none";
    document.getElementById("resetName").style.display = "none";
    document.getElementById("resetPass").style.display = "none";
    document.getElementById("deleteAccount").style.display = "none";
    document.getElementById("manageBlocked").style.display = "block";
    document.getElementById("manageFollowed").style.display = "none";
    
    //blockedUsers;
    //blockedUsersNames = [];
    
    document.getElementById("listOfBlocked").innerHTML = "";
    
    for (var x=0; x < blockedUsers.length; x++) {
        for (var y=0; y < allUsers.length; y++) {
            if (allUsers[y].id == blockedUsers[x]) {
                console.log("In the if");
                //blockedUsersNames.push(allUsers[y].user);
                document.getElementById("listOfBlocked").innerHTML += "<p id='unblockP" + blockedUsers[x] + "' class='followP'><strong>" + allUsers[y].user + "</strong><button class='undoButtons' onclick='unblockUser(" + blockedUsers[x] + ")'>Unblock</button></p>"
            }
        }
    }
    
    if (blockedUsers.length <= 0) {
        document.getElementById("listOfBlocked").innerHTML = "<p>You haven't blocked any users</p>";
    } else {
        document.getElementById("listOfBlocked").innerHTML += "<br>";
    }
    
}

function manageFollowed() {
    document.getElementById("loginFields").style.display = "none";
    document.getElementById("createFields").style.display = "none";
    document.getElementById("loginOptions").style.display = "none";
    document.getElementById("accountView").style.display = "none";
    document.getElementById("forgotPass").style.display = "none";
    document.getElementById("resetMail").style.display = "none";
    document.getElementById("resetName").style.display = "none";
    document.getElementById("resetPass").style.display = "none";
    document.getElementById("deleteAccount").style.display = "none";
    document.getElementById("manageBlocked").style.display = "none";
    document.getElementById("manageFollowed").style.display = "block";
    
    document.getElementById("listOfFollowed").innerHTML = "";
    
    for (var x=0; x < followedUsers.length; x++) {
        for (var y=0; y < allUsers.length; y++) {
            if (allUsers[y].id == followedUsers[x]) {
                console.log("In the if");
                //blockedUsersNames.push(allUsers[y].user);
                document.getElementById("listOfFollowed").innerHTML += "<p id='unfollowP" + followedUsers[x] + "' class='followP'><strong>" + allUsers[y].user + "</strong><button class='undoButtons' onclick='unfollowUser(" + followedUsers[x] + ")'>Unfollow</button></p>"
            }
        }
    }
    
    if (followedUsers.length <= 0) {
        document.getElementById("listOfFollowed").innerHTML = "<p>You haven't followed any users</p>";
    } else {
        document.getElementById("listOfFollowed").innerHTML += "<br>";
    }
    
}

function logOut() {
    session = "";
    
    setCookie("session", session, 1);
    
    makeFormLogin();
    
    userId = -1;
    
    try {
        likedPosts = [];
    
        updateUserLikesDisplay();
        
        filterPosts();
    }
    catch(err) {
      console.log("erro: " + err);   
    }
    
    try {
                        // document.getElementById("userNameDisplay").innerHTML = "name";
                    } catch(error) {
                        console.log(error);
                    }
    //set all like buttons to not logged in state
}

function showLogin() {
    if (document.getElementById("loginDiv").style.display == "block") {
        document.getElementById("loginDiv").style.display = "none";
        //showingAbout = false;
    } else {
        hideWindows();
        document.getElementById("loginDiv").style.display = "block";
        //showingAbout = true;
    }
    
    if (session == "") {
        if (hasVisited != "") {
            makeFormLogin();
        } else {
            makeFormCreate();
        }
    } else {
        showLoggedIn();
    }
}

function hideWindows() {
    
    var windows = document.getElementsByClassName("popup");
    
    for (var i=0; i < windows.length; i++) {
        windows[i].style.display = "none";
    }
}

//var showingFilters = false;

function showFilters() {
    
    if (document.getElementById("filtersDiv").style.display == "block") {
        document.getElementById("filtersDiv").style.display = "none";
        //showingFilters = false;
    } else {
        hideWindows();
        document.getElementById("filtersDiv").style.display = "block";
        //showingFilters = true;
    }
    
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

function successInit(pos) {
    console.log("Location found!");
    var crd = pos.coords;
    long = crd.longitude;
    lat = crd.latitude;
    
    console.log("long " + long);
    console.log("lat " + lat);
    
    if (uName == "appTest") {
        //lat = 40.634311;
        //long = -74.908772;
    }

    oldLong = long;
    oldLat = lat;
    
    try {
        showPosts();
    }
    catch(error) {
        console.log("error is: " + error);
    }
    
    setTimeout(function() { navigator.geolocation.getCurrentPosition(success, error);}, 60000);
}

function success(pos) {
    console.log("Updating Location");
    var crd = pos.coords;
    long = crd.longitude;
    lat = crd.latitude;
    if (distance(lat, oldLat, long, oldLong) > 0.5) {
        showPosts();
        oldLong = long;
        oldLat = lat;
    }
    setTimeout(function() { navigator.geolocation.getCurrentPosition(success, error);}, 60000);
    
    if (uName == "appTest") {
        //lat = 40.634311;
        //long = -74.908772;
    }
    
    //if (session !== "") {
        checkSession();
    //showLoggedIn();
    //}
    
}

function successCam(pos) {
    console.log("Updating Location for cam");
    var crd = pos.coords;
    long = crd.longitude;
    lat = crd.latitude;
    camLat = lat;
    camLong = long;
    if (distance(lat, oldLat, long, oldLong) > 0.5) {
        showPosts();
        oldLong = long;
        oldLat = lat;
    }
    setTimeout(function() { navigator.geolocation.getCurrentPosition(success, error);}, 60000);
    
    if (uName == "appTest") {
        //lat = 40.634311;
        //long = -74.908772;
    }
    
}

function handleError(error) {
    console.log("Error: ", error);
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

function displayNum(num) {
    if (num > 999999) {
        num = num / 1000000;
        num = num.toFixed(1);
        if (num - Math.floor(num) == 0) num = Math.floor(num);
        num = num + "m";
    }
    if (num > 99999) {
        num = num / 1000;
        num = num.toFixed(1);
        if (num - Math.floor(num) == 0) num = Math.floor(num);
        num = num + "k";
    }
    if (num > 9999) {
        num = num / 1000;
        num = num.toFixed(1);
        if (num - Math.floor(num) == 0) num = Math.floor(num);
        num = num + "k";
    }
    if (num > 999) {
        num = num / 1000;
        num = num.toFixed(1);
        if (num - Math.floor(num) == 0) num = Math.floor(num);
        num = num + "k";
    }
    
    return num;
}

function blockUser(id) {
    
    if (session !== "") {
    
        if (!blockedUsers.includes(id)) {
    
           //console.log("Liking Post! working");
            var formData = new FormData();
            formData.append("id", id);
            formData.append("session", session);
    
            var xhttp = new XMLHttpRequest();
    
            // Set POST method and ajax file path
            xhttp.open("POST", "./php/blockUser.php?_='" + Date.now(), true);
    
            // call on request changes state
            xhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
    
                    var response = this.responseText;
                    
                    console.log("response is: " + response);
                    
                    if (response == 1) {
                        
                       console.log("User Blocked");
                       //take action to show user was blocked
                        
                        blockedUsers.push(id);
                        
                        try {
                            document.getElementById("blockButtonProfile").innerHTML = "Unblock";
                            document.getElementById("blockButtonProfile2").innerHTML = "Unblock";
                            document.getElementById("blockButtonProfile").setAttribute("onclick", "unblockUser("+ id + ")");
                            filterPosts();
                        } catch(err) {
                            
                        }
                        
                        document.getElementById("listOfBlocked").innerHTML = "";
    
                        for (var x=0; x < blockedUsers.length; x++) {
                            for (var y=0; y < allUsers.length; y++) {
                                if (allUsers[y].id == blockedUsers[x] && allUsers[y].name != uName) {
                                    console.log("In the if");
                                    //blockedUsersNames.push(allUsers[y].user);
                                    document.getElementById("listOfBlocked").innerHTML += "<p id='unblockP" + blockedUsers[x] + "' class='followP'><strong>" + allUsers[y].user + "</strong><button class='undoButtons' onclick='unblockUser(" + blockedUsers[x] + ")'>Unblock</button></p>"
                                }
                            }
                        }
                        
                        if (blockedUsers.length <= 0) {
                            document.getElementById("listOfBlocked").innerHTML = "<p>You haven't blocked any users</p>";
                        } else {
                            document.getElementById("listOfBlocked").innerHTML += "<br>";
                        }
                        
                        //alert("You have blocked this user! You will no longer see their posts");
                        
                    } else if (response == "emailNotConfirmed") {
                       //console.log("Like Failed");
                        alert("Uh oh! You need to confirm your email before you can block users");
                    } else {
                        alert("Uh oh! That didn't work, please try again later");
                    }
                }
            };
    
            xhttp.send(formData);
            
        } else {
           //console.log("unliking the post");
            
            console.log("User Blocked");
            //take action to show user was blocked
        }
    
    } else {
        alert("Uh oh! It looks like you aren't logged in.");
        makeFormLogin();
        showLogin();
    }
}

function followUser(followid) {
    
    if (session !== "") {
    
        if (!followedUsers.includes(followid)) {
    
           //console.log("Liking Post! working");
            var formData = new FormData();
            formData.append("id", followid);
            formData.append("session", session);
    
            var xhttp = new XMLHttpRequest();
    
            // Set POST method and ajax file path
            xhttp.open("POST", "./php/followUser.php?_='" + Date.now(), true);
    
            // call on request changes state
            xhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
    
                    var response = this.responseText;
                    
                    console.log("follow response is: " + response);
                    
                    if (response == 1) {
                        
                       console.log("User Followed");
                       //take action to show user was Followed
                       
                       try {
                           document.getElementById("followButtonProfile").innerHTML = "Unfollow";
                           document.getElementById("followButtonProfile2").innerHTML = "Unfollow";
                            document.getElementById("followButtonProfile").setAttribute("onclick", "unfollowUser("+ followid + ")");
                       } catch(err) {
                           console.log("follow error " + err);
                       }
                        
                        followedUsers.push(followid);
                        
                        document.getElementById("listOfFollowed").innerHTML = "";
    
                        for (var x=0; x < followedUsers.length; x++) {
                            for (var y=0; y < allUsers.length; y++) {
                                if (allUsers[y].id == followedUsers[x] && allUsers[y].name != uName) {
                                    console.log("In the if");
                                    //blockedUsersNames.push(allUsers[y].user);
                                    document.getElementById("listOfFollowed").innerHTML += "<p id='unfollowP" + followedUsers[x] + "' class='followP'><strong>" + allUsers[y].user + "</strong><button class='undoButtons' onclick='unfollowUser(" + followedUsers[x] + ")'>Unfollow</button></p>"
                                }
                            }
                        }
                        
                        if (followedUsers.length <= 0) {
                            document.getElementById("listOfFollowed").innerHTML = "<p>You haven't followed any users</p>";
                        } else {
                            document.getElementById("listOfFollowed").innerHTML += "<br>";
                        }
                        
                        
                        //update following data like posts folloing have liked
                        
                        //alert("You have blocked this user! You will no longer see their posts");
                        
                    } else if (response == "emailNotConfirmed") {
                       //console.log("Like Failed");
                        alert("Uh oh! You need to confirm your email before you can follow users");
                    } else {
                        alert("Uh oh! That didn't work, please try again later");
                    }
                }
            };
    
            xhttp.send(formData);
            
        } else {
           //console.log("unliking the post");
            
            console.log("User Followed");
            //take action to show user was blocked
        }
    
    } else {
        alert("Uh oh! It looks like you aren't logged in.");
        makeFormLogin();
        showLogin();
    }
}

function unfollowUser(followid) {
    
    if (session !== "") {
    
        if (followedUsers.includes(followid)) {
    
           //console.log("Liking Post! working");
            var formData = new FormData();
            formData.append("id", followid);
            formData.append("session", session);
    
            var xhttp = new XMLHttpRequest();
    
            // Set POST method and ajax file path
            xhttp.open("POST", "./php/unfollowUser.php?_='" + Date.now(), true);
    
            // call on request changes state
            xhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
    
                    var response = this.responseText;
                    
                    console.log("follow response is: " + response);
                    
                    if (response == 1) {
                        
                       console.log("User Unfollowed");
                       //take action to show user was Followed
                       
                       try {
                           document.getElementById("followButtonProfile").innerHTML = "Follow";
                           document.getElementById("followButtonProfile2").innerHTML = "Follow";
                            document.getElementById("followButtonProfile").setAttribute("onclick", "followUser("+ followid + ")");
                       } catch(err) {
                           
                       }
                       
                        try {
                            //unfollowP
                            document.getElementById("unfollowP" + followid).style.display = "none";
                       } catch(err) {
                           
                       }
                        
                        //followedUsers.push(id);
                        
                        const index = followedUsers.indexOf(followid);
                        if (index > -1) { // only splice array when item is found
                          followedUsers.splice(index, 1); // 2nd parameter means remove one item only
                        }
                        
                        //update following data like posts folloing have liked
                        
                        //alert("You have blocked this user! You will no longer see their posts");
                        
                    } else if (response == "emailNotConfirmed") {
                       //console.log("Like Failed");
                        alert("Uh oh! You need to confirm your email before you can follow users");
                    } else {
                        alert("Uh oh! That didn't work, please try again later");
                    }
                }
            };
    
            xhttp.send(formData);
            
        } else {
           //console.log("unliking the post");
            
            console.log("User Followed");
            //take action to show user was blocked
        }
    
    } else {
        alert("Uh oh! It looks like you aren't logged in.");
        makeFormLogin();
        showLogin();
    }
}

function unblockUser(id) {
    
    if (session !== "") {
    
        if (blockedUsers.includes(id)) {
    
           //console.log("Liking Post! working");
            var formData = new FormData();
            formData.append("id", id);
            formData.append("session", session);
    
            var xhttp = new XMLHttpRequest();
    
            // Set POST method and ajax file path
            xhttp.open("POST", "./php/unblockUser.php?_='" + Date.now(), true);
    
            // call on request changes state
            xhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
    
                    var response = this.responseText;
                    
                    console.log("response is: " + response);
                    
                    if (response == 1) {
                        
                       console.log("User UnBlocked");
                       //take action to show user was blocked
                        
                        const index = blockedUsers.indexOf(id);
                        if (index > -1) { // only splice array when item is found
                          blockedUsers.splice(index, 1); // 2nd parameter means remove one item only
                        }
                        
                        //checkSession();
                        
                        try {
                            document.getElementById("blockButtonProfile").innerHTML = "Block";
                            document.getElementById("blockButtonProfile2").innerHTML = "Block";
                            document.getElementById("blockButtonProfile").setAttribute("onclick", "blockUser("+ id + ")");
                            const indexAlt = blockedUsers.indexOf(id);
                            if (indexAlt > -1) { // only splice array when item is found
                            blockedUsers.splice(blockedUsers, 1); // 2nd parameter means remove one item only
                            }
                            
                            filterPosts();
                        } catch(err) {
                            console.log("** Unblock filter posts err " + err);
                        }
                        
                        try {
                            //unfollowP
                            document.getElementById("unblockP" + id).style.display = "none";
                       } catch(err) {
                           
                       }
                        
                        //alert("You have blocked this user! You will no longer see their posts");
                        
                    } else if (response == "emailNotConfirmed") {
                       //console.log("Like Failed");
                        alert("Uh oh! You need to confirm your email before you can block users");
                    } else {
                        alert("Uh oh! That didn't work, please try again later");
                    }
                }
            };
    
            xhttp.send(formData);
            
        } else {
           //console.log("unliking the post");
            
            console.log("User Blocked");
            //take action to show user was blocked
        }
    
    } else {
        alert("Uh oh! It looks like you aren't logged in.");
        makeFormLogin();
        showLogin();
    }
}

function blockUserByName() {
    var uName = document.getElementById("nameManageBlock").value;
    var userExists = false;
    
    for (var c=0; c < allUsers.length; c++) {
        if (uName == allUsers[c].user) {
            userExists = true;
            blockUser(allUsers[c].id);
            document.getElementById("nameManageBlock").value = "";
        }
    }
    
    if (!userExists) alert("Uh oh! We couldn't find a user with that name");
}

function followUserByName() {
    var uName = document.getElementById("nameManageFollow").value;
    var userExists = false;
    
    for (var c=0; c < allUsers.length; c++) {
        console.log("uName is " + uName);
        console.log("allUsers[c].user is " + allUsers[c].user);
        if (uName == allUsers[c].user) {
            userExists = true;
            followUser(allUsers[c].id);
            document.getElementById("nameManageFollow").value = "";
        }
    }
    
    if (!userExists) alert("Uh oh! We couldn't find a user with that name");
}