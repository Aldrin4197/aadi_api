<script setup>
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import axios from 'axios';
import { usePage } from '@inertiajs/vue3';
import { onBeforeUnmount, watch } from 'vue';
import { notifyRealtime, realtimeState } from '@/realtime.mjs';

const page = usePage();
let echo;

function disconnect() {
    realtimeState.connected = false;
    echo?.disconnect();
    echo = undefined;
}

watch(
    () => [page.props.auth?.user?.id, page.props.realtime?.key, page.props.realtime?.cluster],
    ([userId, key, cluster]) => {
        disconnect();
        if (!userId || !key || !cluster) return;

        echo = new Echo({
            broadcaster: 'pusher', key, cluster, Pusher, forceTLS: true, withoutInterceptors: true,
            channelAuthorization: {
                customHandler: ({ socketId, channelName }, callback) => {
                    axios.post('/broadcasting/auth', { socket_id: socketId, channel_name: channelName })
                        .then(({ data }) => callback(null, data), error => callback(error, null));
                },
            },
        });
        const instance = echo;
        const client = instance.connector.pusher;
        client.connection.bind('state_change', ({ current }) => {
            if (echo === instance && current !== 'connected') realtimeState.connected = false;
        });
        instance.private(`users.${userId}`)
            .subscribed(() => {
                if (echo !== instance) return;
                realtimeState.connected = true;
                notifyRealtime('sync');
            })
            .error(() => {
                if (echo !== instance) return;
                realtimeState.connected = false;
                console.warn('Live updates could not authorize the private channel. Periodic refresh remains active.');
            })
            .listen('.activity.changed', event => {
                if (echo === instance && (event.type === 'message' || event.type === 'notification')) notifyRealtime(event.type);
            });
    },
    { immediate: true },
);

onBeforeUnmount(disconnect);
</script>

<template></template>
