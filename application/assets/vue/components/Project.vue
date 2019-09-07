<template>
    <b-card title="test" img-src="https://picsum.photos/300/300/?image=41" img-alt="Image" img-top>
        <b-card-text>
            {{ summary }}
        </b-card-text>
        <template v-slot:footer>
            <small class="text-muted">Last updated 3 mins ago</small>
        </template>
    </b-card>



</template>

<script>
    export default {
        name: "Project",
        props: {
            name: {
                type: String,
                required: true
            },
            summary: {
                type:  String,
                required: false
            }
        }
    };
</script>