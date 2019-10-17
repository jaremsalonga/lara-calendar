<template>
<v-app id="inspire">
    <v-row>
    <v-progress-linear
          :active="loading"
          :indeterminate="loading"
          absolute
          top
          color="primary accent-4"
    ></v-progress-linear>
      <v-col
        sm="12"
        lg="3"
        class="mb-4 controls"
      >
        <v-text-field label="Event Name" v-model="eventName"></v-text-field>
        <v-menu
          ref="menu"
          v-model="menu"
          :close-on-content-click="false"
          :return-value.sync="startDate"
          transition="scale-transition"
          offset-y
          min-width="290px"
        >
          <template v-slot:activator="{ on }">
            <v-text-field
              v-model="startDate"
              label="From"
              append-icon="event"
              readonly
              v-on="on"
            ></v-text-field>
          </template>
          <v-date-picker v-model="startDate" no-title scrollable>
            <v-spacer></v-spacer>
            <v-btn text color="primary" @click="menu = false">Cancel</v-btn>
            <v-btn text color="primary" @click="$refs.menu.save(startDate)">OK</v-btn>
          </v-date-picker>
        </v-menu>
                <v-menu
          ref="menu2"
          v-model="menu2"
          :close-on-content-click="false"
          :return-value.sync="endDate"
          transition="scale-transition"
          offset-y
          min-width="290px"
        >
          <template v-slot:activator="{ on }">
            <v-text-field
              v-model="endDate"
              label="To"
              append-icon="event"
              readonly
              v-on="on"
            ></v-text-field>
          </template>
          <v-date-picker v-model="endDate" no-title scrollable>
            <v-spacer></v-spacer>
            <v-btn text color="primary" @click="menu2 = false">Cancel</v-btn>
            <v-btn text color="primary" @click="$refs.menu2.save(endDate)">OK</v-btn>
          </v-date-picker>
        </v-menu>
        <v-select
          v-model="color"
          :items="colorOptions"
          label="Color"
        ></v-select>
        <template v-for="(day,index) in days">
            <v-checkbox :key="index" v-model="selected" :label="day.label" :value="day.val"></v-checkbox>
        </template>
         <v-btn block depressed small color="primary" @click=saveMe>Save</v-btn>
      </v-col>
      <v-col
        sm="12"
        lg="9"
        class="pl-4"
      >
         <v-sheet height="64">
          <v-toolbar flat color="white">
            <v-btn outlined class="mr-4" @click="setToday">
              Today
            </v-btn>
            <v-btn fab text small @click="prev">
              <v-icon small>mdi-chevron-left</v-icon>
            </v-btn>
            <v-btn fab text small @click="next">
              <v-icon small>mdi-chevron-right</v-icon>
            </v-btn>
            <v-toolbar-title>{{ title }}</v-toolbar-title>
            <v-spacer></v-spacer>
            <v-menu bottom right>
              <template v-slot:activator="{ on }">
                <v-btn
                  outlined
                  v-on="on"
                >
                  <span>{{ typeToLabel[type] }}</span>
                  <v-icon right>mdi-menu-down</v-icon>
                </v-btn>
              </template>
              <v-list>
                <v-list-item @click="type = 'day'">
                  <v-list-item-title>Day</v-list-item-title>
                </v-list-item>
                <v-list-item @click="type = 'week'">
                  <v-list-item-title>Week</v-list-item-title>
                </v-list-item>
                <v-list-item @click="type = 'month'">
                  <v-list-item-title>Month</v-list-item-title>
                </v-list-item>
                <v-list-item @click="type = '4day'">
                  <v-list-item-title>4 days</v-list-item-title>
                </v-list-item>
              </v-list>
            </v-menu>
          </v-toolbar>
        </v-sheet>
        <v-sheet height="90%">
          <v-calendar
            ref="calendar"
            v-model="focus"
            color="primary"
            :events="events"
            :event-color="getEventColor"
            :event-margin-bottom="3"
            :now="today"
            :type="type"
            @click:event="showEvent"
            @click:more="viewDay"
            @click:date="viewDay"
            @change="updateRange"
          ></v-calendar>
          <v-menu
            v-model="selectedOpen"
            :close-on-content-click="false"
            :activator="selectedElement"
            full-width
            offset-x
          >
            <v-card
              color="grey lighten-4"
              min-width="350px"
              flat
            >
              <v-toolbar
                :color="selectedEvent.color"
                dark
              >
                <v-btn icon>
                  <v-icon>mdi-pencil</v-icon>
                </v-btn>
                <v-toolbar-title v-html="selectedEvent.name"></v-toolbar-title>
                <v-spacer></v-spacer>
                <v-btn icon>
                  <v-icon>mdi-heart</v-icon>
                </v-btn>
                <v-btn icon>
                  <v-icon>mdi-dots-vertical</v-icon>
                </v-btn>
              </v-toolbar>
              <v-card-text>
                <span v-html="selectedEvent.details"></span>
              </v-card-text>
              <v-card-actions>
                <v-btn
                  text
                  color="secondary"
                  @click="selectedOpen = false"
                >
                  Cancel
                </v-btn>
              </v-card-actions>
            </v-card>
          </v-menu>
        </v-sheet>
      </v-col>
    </v-row>
  </v-app>
</template>

<script>

import * as Moment from 'moment'
import { extendMoment } from 'moment-range';
import axios from 'axios';

const moment = extendMoment(Moment);

const weekdaysDefault = [0, 1, 2, 3, 4, 5, 6]

const intervalsDefault = {
  first: 0,
  minutes: 60,
  count: 24,
  height: 40,
}

const stylings = {
  default (interval) {
    return undefined
  },
  workday (interval) {
    const inactive = interval.weekday === 0 ||
      interval.weekday === 6 ||
      interval.hour < 9 ||
      interval.hour >= 17
    const startOfHour = interval.minute === 0
    const dark = this.dark
    const mid = dark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)'

    return {
      backgroundColor: inactive ? (dark ? 'rgba(0,0,0,0.4)' : 'rgba(0,0,0,0.05)') : undefined,
      borderTop: startOfHour ? undefined : '1px dashed ' + mid,
    }
  },
  past (interval) {
    return {
      backgroundColor: interval.past ? (this.dark ? 'rgba(0,0,0,0.4)' : 'rgba(0,0,0,0.05)') : undefined,
    }
  },
}
export default {
  data: () => ({
    menu: false,
    loading:false,
    menu2: false,
    today: moment().format('YYYY-MM-DD'),
    focus: moment().format('YYYY-MM-DD'),
    startDate: '',
    endDate : '',
    eventName : '',
    type: 'month',
    typeToLabel: {
      month: 'Month',
      week: 'Week',
      day: 'Day',
      '4day': '4 Days',
    },
    start: null,
    end: null,
    days : [
        {label:'Monday',val:1},
        {label:'Tuesday',val:2},
        {label:'Wednesday',val:3},
        {label:'Thursday',val:4},
        {label:'Friday',val:5},
        {label:'Saturday',val:6},
        {label:'Sunday',val:0},
    ],
    selected: [],
    selectedEvent: {},
    selectedElement: null,
    selectedOpen: false,
        color: 'primary',
    colorOptions: [
      { text: 'Primary', value: 'primary' },
      { text: 'Secondary', value: 'secondary' },
      { text: 'Accent', value: 'accent' },
      { text: 'Red', value: 'red' },
      { text: 'Pink', value: 'pink' },
      { text: 'Purple', value: 'purple' },
      { text: 'Deep Purple', value: 'deep-purple' },
      { text: 'Indigo', value: 'indigo' },
      { text: 'Blue', value: 'blue' },
      { text: 'Light Blue', value: 'light-blue' },
      { text: 'Cyan', value: 'cyan' },
      { text: 'Teal', value: 'teal' },
      { text: 'Green', value: 'green' },
      { text: 'Light Green', value: 'light-green' },
      { text: 'Lime', value: 'lime' },
      { text: 'Yellow', value: 'yellow' },
      { text: 'Amber', value: 'amber' },
      { text: 'Orange', value: 'orange' },
      { text: 'Deep Orange', value: 'deep-orange' },
      { text: 'Brown', value: 'brown' },
      { text: 'Blue Gray', value: 'blue-gray' },
      { text: 'Gray', value: 'gray' },
      { text: 'Black', value: 'black' },
    ],
    events: [
    ],
  }),
  computed: {
    title () {
      const { start, end } = this
      if (!start || !end) {
        return ''
      }

      const startMonth = this.monthFormatter(start)
      const endMonth = this.monthFormatter(end)
      const suffixMonth = startMonth === endMonth ? '' : endMonth

      const startYear = start.year
      const endYear = end.year
      const suffixYear = startYear === endYear ? '' : endYear

      const startDay = start.day + this.nth(start.day)
      const endDay = end.day + this.nth(end.day)

      switch (this.type) {
        case 'month':
          return `${startMonth} ${startYear}`
        case 'week':
        case '4day':
          return `${startMonth} ${startDay} ${startYear} - ${suffixMonth} ${endDay} ${suffixYear}`
        case 'day':
          return `${startMonth} ${startDay} ${startYear}`
      }
      return ''
    },
    monthFormatter () {
      return this.$refs.calendar.getFormatter({
        timeZone: 'UTC', month: 'long',
      })
    },
  },
  mounted () {
    this.$refs.calendar.checkChange()
    this.loading = true;
    axios.get('api/fetchEvent').then((response) => {
        this.events.push(...response.data)
        this.loading = false;
    })
  },
  methods: {
    async saveMe() {
        this.loading = true;
        let events = await this.filterDate();
        axios.post('api/insertEvents',events).then((response) => {
            this.events.push(...response.data)
            this.loading = false;
        })
    },
    filterDate() {
        let start = moment(this.startDate);
        let end = moment(this.endDate);
        let current = start.clone();
        let range = moment.range(start, end);
        let localCopy = [];
        for (let day of range.by('day')) {
            if(this.selected.includes(Number(day.format('d')))) {
                localCopy.push({
                    name: this.eventName,
                    start: day.format('YYYY-MM-DD'),
                    color: this.color,
                })
            }
        }
        return localCopy;
    },
    viewDay ({ date }) {
      this.focus = date
      this.type = 'day'
    },
    getEventColor (event) {
      return event.color
    },
    setToday () {
      this.focus = this.today
    },
    prev () {
      this.$refs.calendar.prev()
    },
    next () {
      this.$refs.calendar.next()
    },
    showEvent ({ nativeEvent, event }) {
      const open = () => {
        this.selectedEvent = event
        this.selectedElement = nativeEvent.target
        setTimeout(() => this.selectedOpen = true, 10)
      }

      if (this.selectedOpen) {
        this.selectedOpen = false
        setTimeout(open, 10)
      } else {
        open()
      }

      nativeEvent.stopPropagation()
    },
    updateRange ({ start, end }) {
      // You could load events from an outside source (like database) now that we have the start and end dates on the calendar
      this.start = start
      this.end = end
    },
    nth (d) {
      return d > 3 && d < 21
        ? 'th'
        : ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'][d % 10]
    },
  },
}

</script>

<style lang='scss'>

@import '~@fullcalendar/core/main.css';
@import '~@fullcalendar/daygrid/main.css';

</style>