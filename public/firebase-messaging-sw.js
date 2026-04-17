// public/firebase-messaging-sw.js
// Background message handler untuk FCM
// Tampilkan notifikasi browser saat tab tidak aktif

importScripts('https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js')
importScripts('https://www.gstatic.com/firebasejs/10.7.1/firebase-messaging-compat.js')

// Ganti nilai di bawah ini dengan konfigurasi Firebase project Anda
// Nilai ini bisa ditemukan di Firebase Console > Project Settings > Your apps
firebase.initializeApp({
  apiKey: '',
  authDomain: '',
  projectId: '',
  storageBucket: '',
  messagingSenderId: '',
  appId: '',
})

const messaging = firebase.messaging()

messaging.onBackgroundMessage((payload) => {
  self.registration.showNotification(payload.notification.title, {
    body: payload.notification.body,
    icon: '/favicon.ico',
    data: payload.data,
  })
})
