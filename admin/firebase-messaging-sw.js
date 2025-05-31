importScripts('https://www.gstatic.com/firebasejs/8.3.2/firebase-app.js');
importScripts('https://www.gstatic.com/firebasejs/8.3.2/firebase-messaging.js');

firebase.initializeApp({
    apiKey: "AIzaSyA1it8vm4VSC4gMRWqv4ihltarf6UJyqp8",
    authDomain: "medical-store-user.firebasestorage.app",
    projectId: "medical-store-user",
    storageBucket: "medical-store-user.firebasestorage.app",
    messagingSenderId: "593043511096",
    appId: "593043511096",
    measurementId: "593043511096"
});

const messaging = firebase.messaging();
messaging.setBackgroundMessageHandler(function (payload) {
    return self.registration.showNotification(payload.data.title, {
        body: payload.data.body ? payload.data.body : '',
        icon: payload.data.icon ? payload.data.icon : ''
    });
});