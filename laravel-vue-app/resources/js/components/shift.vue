<template>
    <div>
        <h1>シフト</h1>
        <button type="button" @click="logout">ログアウト</button>
        <div id="month" >
            <b-button v-on:click="changeMonth(-1)">前月</b-button>
            <label>{{this.drawMonth}}</label>
            <b-button v-on:click="changeMonth(1)">次月</b-button>
        </div>

    </div>
</template>
/*
## カレンダーを表示
* DBにといあわせてある場合は表示
* ない場合はつくる
* 月分の一覧を表示
* その月の労働時間の集計

<script>
export default {
    data()
    {
        return {
            user:         "",
            thisMonth:    "",
            drawMonth:    "",
            scheduleTable:   [],
            workingTable: [],
        };
    },
    mounted()
    {
        let d          = new Date();
        this.thisMonth = d;
        var thisYear   = d.getFullYear();
        var thisMonth  = d.getMonth()+1;
        this.drawMonth = thisYear + '年' + thisMonth + '月';
        this.getSchedule();
    },
    methods: {
        getSchedule(){
            axios.get("/api/user").then(response => {
                axios.post("/api/getSchedule/").then(response => {
                    if( !response.data )
                    {
                       this.makeSchedule();
                    }
                    this.scheduleTable = response.data;
                }).catch(error => {
                    console.log(error);
                })
            });

        },
        drawSchedule(){},
        drawWorkingTable(){},
        noticeHardWork(){},
        makeSchedule(){},
        changeMonth(valueMonth){
            var d = this.thisMonth;
            d.setMonth(d.getMonth() + valueMonth);
            var thisYear  = d.getFullYear();
            var thisMonth = d.getMonth()+1;
            this.thisMonth = d;
            this.drawMonth = thisYear + '年' + thisMonth + '月';
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
