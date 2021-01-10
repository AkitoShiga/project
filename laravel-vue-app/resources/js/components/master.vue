<template>
    <div>
        <h1>master</h1>
        <h2>メンバー一覧</h2>
            <table class="table table-borderd">
                <thead class="thead-dark">
                    <tr>
                    <th>ID</th>
                    <th>性</th>
                    <th>名</th>
                    <th>削除</th>
                    </tr>
                </thead>
          <tbody >
		    <tr v-for="member in this.members" v-bind:key="member.id">
            <td>{{member.id}}</td>
			<td>{{member.sei}}</td>
			//<td>{{member.mei}}</td>
            <td><button class="btn btn-danger" v-on:click="deleteMember(member.id)">削除</button></td>
            </tr>
          </tbody>
        </table>
        <form @submit.prevent="addMenber">
            <h2>メンバーの追加</h2>
            <input type="text" v-model="sei" placeholder="姓">
            <input type="text" v-model="mei" placeholder="名">
            <b-button>追加</b-button>
        </form>
        <b-button type="button" @click="logout">ログアウト</b-button>
    </div>
</template>

<script>
export default {
    data() {
        return {
            add_member :{
            sei: "",
            mei: "",
            },
            delete_member :{
                id:1,
            },
            members:{},
        };
    },
    mounted() {
        var self = this;
        axios.get("/api/getMembers").then(response => {
            this.members = response.data;
            console.log(self.members);
            console.log('hoihoiho');
            });
        axios.get("/api/user").then(response => {
            self.user = response.data;
        });
    },
    methods: {

        deleteMember(deleteId) {
            let data = {
                id: deleteId
            }
            axios.post("api/deleteMembers", data).then(response => {
                console.log(response.message);
            });
            this.$router.go({path: this.$router.currrentRoute.path, force:true});

        },
        addMember() {
            let data = {
                sei: this.add_member.sei,
                mei: this.add_member.mei,
            }
            axios.post("api/add_menber",data);
            this.$router.go({path: this.$router.currentRoute.path, force:true});
        },
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
