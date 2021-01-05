<template>
    <div>
        <h1>シフト</h1>
        <button type="button" @click="logout">ログアウト</button>
    </div>
</template>

<script>
export default {
    data() {
        return {
            user: ""
        };
    },
    mounted() {
        axios.get("/api/user").then(response => {
            this.user = response.data;
            console.log(this.data);
        });
    },
    methods: {
        logout() {
            axios
                .post("api/logout")
                .then(response => {
                    console.log(response);
                    localStorage.removeItem("auth");
                    this.$router.push("/login");
                })
                .catch(error => {
                    console.log(error);
                });
        }
    }
};
</script>