<template>
    <div>
        <h1>シフト</h1>
        <div id="month" >
            <b-button v-on:click="changeMonth(-1)">前月</b-button>
            <label>{{this.drawMonth}}</label>
            <b-button v-on:click="changeMonth(1)">次月</b-button>
        </div>
        <div id="scheduleTable">
            <table class="table table-borderd">
                <thead class="thead-dark">
                    <tr>
                        <th>日付</th>
                        <th>曜日</th>
                        <th v-for="shift in this.scheduleTable[ 0 ].shifts" v-bind:key="scheduleTable[ 0 ].shifts.id">
                            {{ shift.time }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                <tr v-for="schedule in this.scheduleTable" v-bind:key="scheduleTable.id">
                    <td>{{ schedule.date }}</td>
                    <td :class="getWeekColor( schedule )">{{  getWeekChar( schedule ) }}</td>
                    <td v-for="shift in schedule.shifts" v-bind:key="schedule.shifts.id">
                        {{ shift.members }}
                    </td>
                </tr>
                </tbody>
            </table>
            <h1>稼働時間</h1>
            <div id="workingTable">
                <table class="table table-borderd">
                    <thead class="thead-dark">
                        <tr>
                            <th>名前</th>
                            <th>稼働時間(H)</th>
                        </tr>
                    </thead>
                    <tbody>
                    <tr v-for="workingInfo in this.totalWorkingTable" v-bind:key="totalWorkingTable.id">
                        <td>{{ workingInfo.name }}</td>
                        <td>{{ workingInfo.hours }}</td>
                    </tr>
                    </tbody>
                </table>

            </div>
        </div>
        <button class="btn btn-primary" type="button" @click="logout">ログアウト</button>
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
            user:               "",
            thisDateTime:       "",
            thisMonth:          "",
            thisYear:           "",
            drawMonth:          "",
            scheduleTable:      [],
            totalWorkingTable:  [],
            weekArray:          ['日', '月', '火', '水', '木', '金', '土' ],
        };
    },
    mounted()
    {
        let d             = new Date();
        this.thisDateTime = d;
        this.thisYear     = d.getFullYear();
        this.thisMonth    = d.getMonth() + 1;
        this.drawMonth    = this.thisYear + '年' + this.thisMonth + '月';
        this.getSchedule();
        this.getTotalWorkingHours();
    },
    methods: {
        getTotalWorkingHours()
        {
          let data =
              {
                thisYear : this.thisYear,
                thisMonth : this.thisMonth,
              }
              axios.post( '/api/getTotalWorkingHours', data )
                   .then( response => this.totalWorkingTable = response.data )
                   .catch( error => console.log( error ))
        },
        getWeekChar( schedule )
        {
            let weekChar  = this.weekArray[ schedule.week ];
            let isHoliday = schedule.isHoliday;
            if( isHoliday ) { weekChar += '・祝'; }
            return  weekChar;

        },
        getWeekColor( schedule )
        {
           let weekChar = this.getWeekChar( schedule );
                if( weekChar === "土")   { return "saturday"; }
           else if( weekChar === "日")   { return "sunday"; }
           else if( weekChar.length > 1) { return "holiday"; }
           else return( "normal" );

        },
        getSchedule(){
            var data =
            {
                thisYear:  this.thisYear,
                thisMonth: this.thisMonth,
            };
            axios.get( "/api/user" )
                 .then( response => {
                    axios.post("/api/getSchedule/", data )
                         .then( response =>
                             {
                               this.scheduleTable = response.data;
                                this.scheduleTable.sort( function( val1, val2 )
                                {
                                    let val1Date = val1.date;
                                    let val2Date = val2.date;
                                    val1Date     = val1Date.substr( 8, 2 );
                                    val2Date     = val2Date.substr( 8, 2 );
                                    return val1Date > val2Date? 1 : -1;
                                })
                            })
                        .catch( error => { console.log( error ); } )
            });
        },
        changeMonth( valueMonth )
        {
            var d = this.thisDateTime;
            d.setMonth(d.getMonth() + valueMonth );
            this.thisYear  = d.getFullYear();
            this.thisMonth = d.getMonth() + 1;
            this.drawMonth = this.thisYear + '年' + this.thisMonth + '月';
            this.getSchedule();
        },
        logout()
        {
            axios
                .post( "api/logout" )
                .then( response => {
                    console.log( response );
                    localStorage.removeItem( "auth" );
                    this.$router.push( "/login" );
                })
                .catch( error => {
                    console.log( error );
                }
            );
        }
    }
};
</script>
<style>
.saturday { color:blue;  }
.sunday   { color:red;   }
.holiday  { color:red;   }
.normal   { color:black; }
</style>
