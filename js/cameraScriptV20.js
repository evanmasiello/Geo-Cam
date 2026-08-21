var invertCamera = true;

var blockedUsers = [];
var followedUsers = [];

//set zoom factor based on cookie
var zoomFactor = 0;

var session = getCookie("session");

console.log("session: " + session);

if (session !== "") {
    checkSession(false);
    showLoggedIn();
}//header

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

function setPadderHeight() {
    var clientHeight = document.getElementById('content').clientHeight;

    var clientHeightHead = document.getElementById('header').clientHeight;
    
    //var clientHeightFoot = document.getElementById('footer').clientHeight;
    
    let height = window.innerHeight;
    
    //console.log("inner html is: " + document.getElementById("offsetDiv").innerHTML);
    
    var heightVar = height - clientHeight;// - clientHeightHead;
    
    if (heightVar < 0) {
        heightVar = 0;
        // document.getElementById("html").style.overflow = "auto";
    } else {
        // document.getElementById("html").style.overflow = "hidden";
    }
    
    document.getElementById("offsetDiv").style.height = heightVar + 10 + "px";
}

setPadderHeight();

var oldScroll = window.scrollY;

window.onresize = function() {setPadderHeight();};

                    window.addEventListener('scroll', () => {

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

navigator.geolocation.getCurrentPosition(successInit);

function error() {
    
}

const inputElementZoom = document.getElementById("zoomRange");
let isChangingZoom = false;

const setCSSPropertyZoom = () => {
  const percent =
    ((inputElementZoom.value - inputElementZoom.min) /
    (inputElementZoom.max - inputElementZoom.min)) *
    100;
  // Here comes the magic
  inputElementZoom.style.setProperty("--webkitProgressPercent", `${percent}%`);
}

// Set event listeners
const handleMoveZoom = () => {
  if (!isChangingZoom) return;
  setCSSPropertyZoom();
};
const handleUpAndLeaveZoom = () => isChangingZoom = false;
const handleDownZoom = () => isChangingZoom = true;

inputElementZoom.addEventListener("mousemove", handleMoveZoom);
inputElementZoom.addEventListener("mousedown", handleDownZoom);
inputElementZoom.addEventListener("mouseup", handleUpAndLeaveZoom);
inputElementZoom.addEventListener("mouseleave", handleUpAndLeaveZoom);
inputElementZoom.addEventListener("click", setCSSPropertyZoom);

// Init input
setCSSPropertyZoom();

function showLogin() {
    if (document.getElementById("loginDiv").style.display == "block") {
        document.getElementById("loginDiv").style.display = "none";
        //showingAbout = false;
    } else {
        hideWindows();
        document.getElementById("loginDiv").style.display = "block";
        //showingAbout = true;
    }
}

// JavaScript program to calculate Distance Between
// Two Points on Earth

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

function saveImage() {
    
//     var link = document.getElementById('downloadLink');
    
//     if (!isInApp) {
//         image = canvas.toDataURL("image/png").replace("image/png", "image/octet-stream");
//       link.setAttribute('download', '');
//       link.setAttribute('target', '_blank');
//       link.download = "myGeoCamPost.png";
//       link.href = image;
//       //link.click(); 
//     } else {
//       link.onclick = function() {alert('We are currently having issues with the download feature.')};
//   }
}

const inputElement = document.querySelector('[type="range"]');
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
//var showingGuidlines = false;

var zoomCent = 100 + (parseInt(zoomFactor));
var padCent = parseInt(zoomFactor) / 2;

var timer = "null";

function zoomCam(object) {
    
    console.log("zooming");
    
    console.log("object is: " + object);
    
    console.log("currIndex " + currIndex);
    
    if (currIndex < 0) currIndex = 0;
    
    useCamera(videoDevices[currIndex]);
    
    setCSSPropertyZoom();
    
    zoomFactor = object.value;
    
    console.log("zoomFactor is: " + zoomFactor);
    
    var videoHolder = document.getElementById("playerHolder");
    
    zoomCent = 100 + (parseInt(zoomFactor));
    padCent = parseInt(zoomFactor) / 2;
    
    console.log("zoom cent " + zoomCent);
    console.log("pad cent " + padCent);
    
    var ZoomStr = zoomCent.toString() + "%;";
    var PadStr = "-" + padCent.toString() + "%;";
    
    console.log("zoom value " + ZoomStr);
    console.log("pad value " + PadStr);
    
    videoHolder.setAttribute('style', "");
    
    videoHolder.setAttribute('style', videoHolder.getAttribute('style') + 'width: '+ZoomStr); //.style.width = ZoomStr;
    videoHolder.setAttribute('style', videoHolder.getAttribute('style') + 'margin-left: '+PadStr); //.style.marginLeft = PadStr;
    videoHolder.setAttribute('style', videoHolder.getAttribute('style') + 'margin-top: '+PadStr); //.style.marginTop = PadStr;
    
    //document.getElementById("player").width = "150%;";
    
    setTimeout(function() { useCamera(videoDevices[currIndex]);}, 10);
}

var goingToPost = false;

function showGuidelines() {
    if (document.getElementById("guidelinesDiv").style.display == "block") {
        document.getElementById("guidelinesDiv").style.display = "none";
        //showingGuidlines = false;
    } else {
        hideWindows();
        document.getElementById("guidelinesDiv").style.display = "block";
        //showingGuidlines = true;
    }
}

function invertCam() {
    if (invertCamera) {
        invertCamera = false;
        player.style = "";
    } else {
        invertCamera = true;
        player.style += "-moz-transform: scale(-1, 1); \
-webkit-transform: scale(-1, 1); -o-transform: scale(-1, 1); \
transform: scale(-1, 1); filter: FlipH;";
    }
}

function flip_image(canvas) {
    var context = canvas.getContext('2d');
    var imageData = context.getImageData(0, 0, canvas.width, canvas.height);
    var imageFlip = new ImageData(canvas.width, canvas.height);
    var Npel = imageData.data.length / 4;

    for (var kPel = 0; kPel < Npel; kPel++) {
        var kFlip = flip_index(kPel, canvas.width, canvas.height);
        var offset = 4 * kPel;
        var offsetFlip = 4 * kFlip;
        imageFlip.data[offsetFlip + 0] = imageData.data[offset + 0];
        imageFlip.data[offsetFlip + 1] = imageData.data[offset + 1];
        imageFlip.data[offsetFlip + 2] = imageData.data[offset + 2];
        imageFlip.data[offsetFlip + 3] = imageData.data[offset + 3];
    }

    var canvasFlip = document.createElement('canvas');
    canvasFlip.setAttribute('width', canvas.width);
    canvasFlip.setAttribute('height', canvas.width);

    canvasFlip.getContext('2d').putImageData(imageFlip, 0, 0);
    return canvasFlip;
}

function flip_index(kPel, width, height) {
    var i = Math.floor(kPel / width);
    var j = kPel % width;
    var jFlip = width - j - 1;
    var kFlip = i * width + jFlip;
    return kFlip;
}

function resetCam() {
    
    document.getElementById("loadingOverlay").style.display = "none";
    
    if (uploadRequest !== "unset") {
        uploadRequest.abort();
        uploadRequest = "unset";
    }
    
    document.getElementById("upload").style.display = "inline-block";
    
    document.getElementById("editPostVisSelectCam").value = "all";
    
    if (videoDevices.length == 1) {
        try {
            navigator.mediaDevices.getUserMedia(constraints).then((stream) => {
            // Attach the video stream to the video element and autoplay.
            document.getElementById('player').srcObject = stream;});
        }
        catch(err) {
            console.log("camera problem");
        }
    } else {
        try {
            useCamera(videoDevices[currIndex]);
        }
        catch(err) {
            console.log("came problem");
        }
    }

    //if (!invertCamera) {
    //invertCam();
    //}

    document.getElementById('takeButtons').style.display = "block";
    document.getElementById('uploadButtons').style.display = "none";

    document.getElementById('canvas').style.display = "none";
    document.getElementById('stage').style.display = "block";
}

var uploadRequest = "unset";

var agreedToGuidlines = false;
var guidlinesCookie = getCookie("guidelines");

if (guidlinesCookie == "1") {
    agreedToGuidlines = true;
    document.getElementById("rememberAccept").checked = true;
    setCookie("guidelines",1,999);
}

function agreeToGuidelines() {
    console.log("agreed to guidelines");
    agreedToGuidlines = true;
    if (document.getElementById("rememberAccept").checked) {
        setCookie("guidelines","1",999);   
    }
    showGuidelines();
    if (goingToPost) {
        finishUpload();
    }
    
    goingToPost = false;
}

function finishUpload() {
    var formData = new FormData();
                formData.append("image", imageDataUrl);
                if (camLat !== undefined && camLat !== null) formData.append("lat", camLat);
                if (camLong !== undefined && camLong !== null) formData.append("long", camLong);
                formData.append("session", session);
                formData.append("vis", document.getElementById("editPostVisSelectCam").value);
                console.log("Session is this: " + session);
                
                console.log("old upload request is: " + uploadRequest);
                
                if (uploadRequest !== "unset") {
                    uploadRequest.abort();
                    //uploadRequest = "unset";
                }
        
                uploadRequest = new XMLHttpRequest();
        
                // Set POST method and ajax file path
                uploadRequest.open("POST", "./php/createJsonPost.php?_='" + Date.now(), true);
        
                // call on request changes state
                uploadRequest.onreadystatechange = function() {
                    if (this.readyState == 4 && this.status == 200) {
        
                        var response = this.responseText;
                        if (response == 1) {
                            document.getElementById("loadingOverlay").style.display = "none";
                            
                            postCount++;
                            
                            document.getElementById("postCount").innerHTML = displayNum(postCount);
                            
                            sendUpdate("Epic! " + document.getElementById("name").innerHTML + " just made a post! Its the " + ordinal_suffix_of(parseInt(document.getElementById("postCount").innerHTML)) + " post that they've made");
                            
                            document.getElementById("upload").style.display = "none";
                            
                            alert("Congrats! You made a post!");
                            
                        } else if (response == "emailNotConfirmed") {
                           //console.log("Like Failed");
                            alert("Uh oh! You need to confirm your email before you can upload posts");
                            document.getElementById("loadingOverlay").style.display = "none";
                        } else {
                            alert("Uh oh, the post couldn't be uploaded");
                            document.getElementById("loadingOverlay").style.display = "none";
                        }
                        
                        uploadRequest = "unset";
                    }
                };
        
                // Send request with data
                uploadRequest.send(formData);
}

function uploadFile() {
    //console.log("in upload");
    
    if (session !== "") {
        
        console.log("session is defined");

        document.getElementById("loadingOverlay").style.display = "block";
    
        if (long === null && lat === null) {
            alert("Oh no! We can't find where you took this picture, please allow us to access your location, reload the page, and try again");
            navigator.geolocation.getCurrentPosition(success);
            document.getElementById("loadingOverlay").style.display = "none";
        } else if (imageDataUrl !== null) {
            //console.log("picture data is here!");
            
            if (!agreedToGuidlines) {
                alert("Please agree to our guidlines to complete your post");
                showGuidelines();
                goingToPost = true;
            } else {
                finishUpload();
            }
    
        } else {
            alert("Please take a picture");
            document.getElementById("loadingOverlay").style.display = "none";
        }
    
    } else {
        alert("Uh oh! It looks like you aren't logged in.");
        makeFormLogin();
        showLogin();
    }

}

//camera
const player = document.getElementById('player');
const canvas = document.getElementById('canvas');
const context = canvas.getContext('2d');
const captureButton = document.getElementById('capture');

//canvas.width = window.getComputedStyle(player).getPropertyValue('width');
//player.height = screen.width - 10;

var constraints = {
    video: {
        width: {
            min: 240,
            ideal: 1080,
            max: 1920
        },
        height: {
            min: 240,
            ideal: 1080,
            max: 1920
        },
        aspectRatio: {
            min: 1,
            max: 1,
            ideal: 1
        }
    }
};

//Temporary
const videoElement = document.querySelector("video");
const toggleButton = document.querySelector("#toggleCam");

let videoDevices;
let currIndex = -1;

function init() {
    console.log("in init");
    
    if (navigator.mediaDevices && navigator.mediaDevices.enumerateDevices) {
        
        console.log("in get devices");
        
        navigator.mediaDevices
            .getUserMedia(constraints)
            .then(() => navigator.mediaDevices.enumerateDevices())
            .then(deviceInfos => {
                videoDevices = Array.from(deviceInfos).filter(item => item.kind == "videoinput");

                if (videoDevices.length === 0 || videoDevices === null || videoDevices === undefined) {
                    console.log("No Camera");
                    noCameraFound();
                } else if (videoDevices.length == 1) {
                    console.log("1 Camera");
                    const label = videoDevices[0].label || "Default Camera";
                    //toggleButton.textContent = `Using ${label}`;
                    //toggleButton.setAttribute("disabled", "1");
                    useCamera(videoDevices[0]);
                } else if (videoDevices.length >1 ) {
                    console.log("Many Camera");
                    toggleCamera();
                    toggleButton.addEventListener("click", toggleCamera);
                } else {
                    console.log("No Camera");
                    noCameraFound();
                }
            });
    } else {
        noCameraFound();
    }
}

function noCameraFound() {
    alert("Uh oh! We couldn't find any cameras connected to your device.");
    //toggleButton.textContent = "No Camera";
    //toggleButton.setAttribute("disabled", "1");
}

function useCamera(device) {
    if (videoElement && videoElement.srcObject && videoElement.srcObject.getTracks) {
        videoElement.srcObject.getTracks().forEach(track => track.stop());
    }

    const deviceId = device.deviceId;
    
    console.log("camera id: " + deviceId);
    console.log("camera lable: " + device.label);
    console.log("camera facing mode: " + device.facingMode);
    
    if (device.label.includes("back") || device.label.includes("enviroment") || device.facingMode == "enviroment" || device.label.includes("Back") || device.label.includes("Enviroment") || device.facingMode == "Enviroment") {
        invertCamera = false;
        console.log("Cam is facing enviroment");
        
        player.style = "";
        
    } else {
        invertCamera = true;
        console.log("Cam is facing user");
        
        player.style += "-moz-transform: scale(-1, 1); \
-webkit-transform: scale(-1, 1); -o-transform: scale(-1, 1); \
transform: scale(-1, 1); filter: FlipH;";
    }
    
    var constraints = {
        video: {
            deviceId: deviceId ? {
                exact: deviceId
            } : undefined,
            width: {
                min: 240*(zoomCent/100),
                ideal: 1080*(zoomCent/100),
                max: 1920*(zoomCent/100)
            },
            height: {
                min: 240*(zoomCent/100),
                ideal: 1080*(zoomCent/100),
                max: 1920*(zoomCent/100)
            },
            aspectRatio: {
                min: 1,
                max: 1,
                ideal: 1
            }
        }
    };

    navigator.mediaDevices
        .getUserMedia(constraints)
        .then(stream => {
            videoElement.srcObject = stream;
        })
        .catch();
}

function toggleCamera() {
    currIndex = (currIndex + 1) % videoDevices.length;
    useCamera(videoDevices[currIndex]);

    const nextIndex = (currIndex + 1) % videoDevices.length;
    const label = videoDevices[nextIndex].label || `Camera ${nextIndex}`;
    //toggleButton.textContent = `Use ${label}`;
    
    if (videoDevices.length > 1) {
        //invertCam();
    }
}

function getInnerWidth(elem) {
    return parseFloat(window.getComputedStyle(elem).width);
}

var camLat;
var camLong;

function captureClick() {
    document.getElementById("loadingOverlay").style.display = "none";
    document.getElementById("takingText").style.display = "none";
    
      image = canvas.toDataURL("image/png").replace("image/png", "image/octet-stream");
    //   var link = document.getElementById('downloadLink');
    //   link.setAttribute('download', 'myGeoCamPost.png');
    //   link.setAttribute('target', '_blank');
    //   link.setAttribute('href', image);

    if (lat === null || long === null) {
        navigator.geolocation.getCurrentPosition(successCam);
    } else {
        camLat = lat;
        camLong = long;
        
        console.log("cam lat is: " + camLat);
        console.log("cam long is: " + camLong);
    }

    //canvas.style.height = player.style.height;
    //canvas.style.width = player.style.width;
    
    //context.canvas.width  = window.innerWidth - 10;
    //context.canvas.height = window.innerWidth - 10;

    //Use this to zoom \/
    //context.drawImage(player, -400, -400, context.canvas.height+400, context.canvas.width+400);
    
    //var dimensions = 1080 + Math.round(1080*((parseInt(zoomFactor))/100));
    //var dimensions = Math.round()*2;
    
    var defaultDist = context.canvas.height;
    //defaultDist = 1080;
    
    if (zoomCent == 0) {
        var dimensions = 1080;
    } else {
        var dimensions = defaultDist * (zoomCent)/100;   
    }
    
    console.log("canvas height: " + context.canvas.height);
    
    //var padCent = parseInt(zoomFactor) / 2;
    
    //console.log("the pad cent is: " + padCent);
    
    var start = (dimensions-defaultDist)/2;
    //var start = 0;
    
    if (dimensions < 1080) {
        dimensions = 1080;
        start = 0;
    }
    
    console.log("start is: " + start);
    console.log("dimensions is: " + dimensions);
    
    //context.drawImage(player, -start, -start, dimensions, dimensions);
    context.drawImage(player, -start, -start, dimensions, dimensions);
    
    if (invertCamera) {
 
        context.drawImage(flip_image(canvas), 0, 0, defaultDist, defaultDist);
    
    }

    // Stop all video streams.
    player.srcObject.getVideoTracks().forEach(track => track.stop());
    canvas.style.display = "block";
    document.getElementById("stage").style.display = "none";
    //document.getElementById('capture').onclick = "";
    document.getElementById('takeButtons').style.display = "none";
    document.getElementById('uploadButtons').style.display = "block";
    //document.getElementById('capture').onclick = "resetCam()";
    //document.getElementById("loadingOverlay").style.display = "block";

    imageDataUrl = canvas.toDataURL();
}

player.setAttribute("playsinline", true);

if (invertCamera) {
 
    player.style += "-moz-transform: scale(-1, 1); \
-webkit-transform: scale(-1, 1); -o-transform: scale(-1, 1); \
transform: scale(-1, 1); filter: FlipH;";
    
}
var playerHeight = document.getElementById("player").clientHeight;

try {
        navigator.mediaDevices.getUserMedia(constraints).then((stream) => {
// Attach the video stream to the video element and autoplay.
            player.srcObject = stream;
            var playerHeight = document.getElementById("player").clientHeight;
});
} catch(err) {
    console.log("in catch");
    var stageWidth = document.getElementById('stage').clientWidth;

    if (stageWidth > 0.75*playerHeight) document.getElementById('stage').style.height = stageWidth + "px";
}


init();
//window.onscroll = function() {myFunction()};

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

function timerClick(waitTime) {
    setTimeout(function() { captureClick();}, waitTime*1000);
    document.getElementById("takeButtons").style.display = "none";
    document.getElementById("loadingOverlay").style.display = "block";
    document.getElementById("takingText").style.display = "block";
}

function captureWithFlash(waitTime) {
    console.log("Using flash");
    document.getElementById("whiteCover").style.display = "block";
    timerClick(waitTime);
    setTimeout(function() { document.getElementById("whiteCover").style.display = "none";}, waitTime*2000);
}

function clickFlash() {
    if (!flashOn) {
        flashOn = true;
        document.getElementById("flashButton").className = "fa-solid fa-bolt-lightning";
    } else {
        flashOn = false;
        document.getElementById("flashButton").className = "fa-solid fa-bolt";
    }
}

function takePhoto() {
    if (!timerSet && !flashOn) {
        captureClick();
    } else if (timerSet) {
        timerClick(5);
    } else {
        captureWithFlash(0.25);
    }
}

var timerSet = false;
var flashOn = false;

function clickTimerBttn() {
    if (!timerSet) {
        timerSet = true;
        document.getElementById("timerButton").className = "fa-solid fa-clock";
    } else {
        timerSet = false;
        document.getElementById("timerButton").className = "fa-regular fa-clock";
    }
}