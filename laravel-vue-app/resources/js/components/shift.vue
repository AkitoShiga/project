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
            user:          "",
            thisDateTime:  "",
            thisMonth:     "",
            thisYear:      "",
            drawMonth:     "",
            scheduleTable: [],
            workingTable:  [],
        };
    },
    mounted()
    {
        let d              = new Date();
        this.thisDateTime = d;
        this.thisYear       = d.getFullYear();
        this.thisMonth      = d.getMonth()+1;
        this.drawMonth     = this.thisYear + '年' + this.thisMonth + '月';
        this.getSchedule();
    },
    methods: {
        getSchedule(){
            var data = {
                thisYear:  this.thisYear,
                thisMonth: this.thisMonth,
            };
            axios.get("/api/user").then(response => {
                axios.post("/api/getSchedule/", data ).then(response => {
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
            var d = this.thisDateTime;
            d.setMonth(d.getMonth() + valueMonth);
            this.thisYear  = d.getFullYear();
            this.thisMonth = d.getMonth()+1;
            this.drawMonth = this.thisYear + '年' + this.thisMonth + '月';
            this.getSchedule();
            //hoiho;
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
