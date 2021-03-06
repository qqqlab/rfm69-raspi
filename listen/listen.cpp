#define INIFILE "../config/listen.ini"

#include <vector>
#include <string>
using namespace std;

#include "../lib/inih/cpp/INIReader.h"

#include <bcm2835.h>
#include <stdio.h>
#include <signal.h>
#include <unistd.h>
#include <stdarg.h>

#include <RH_RF69.h>

#include <sys/time.h>
#include <time.h>

extern RH_RF69 rf69;


void fprintDate(FILE* f) {
  struct timeval tv;
  gettimeofday(&tv, NULL);

  //header
  char datestr[26];
  strftime(datestr, 26, "%Y-%m-%d %H:%M:%S", localtime(&tv.tv_sec));
  fprintf(f, "%d.%06d\t%s\t", tv.tv_sec, tv.tv_usec, datestr);
}

time_t getMinute() {
  struct timeval tv;
  gettimeofday(&tv, NULL);
  return (tv.tv_sec / 60) * 60;
}

int str2arr(const char* str, const char* seps, int *output)
{
  char tokenstring[1024];
  strncpy(tokenstring,str,sizeof(tokenstring));
  char* token;
  int i = 0;

  token = strtok (tokenstring, seps);
  while (token != NULL)
  {
    output[i++] = atoi(token);
    token = strtok (NULL, seps);
  }
  return i;
}

vector<string> split(const string& s, string c) {
   vector<string> v;
   string::size_type i = 0;
   string::size_type j = s.find(c);

   while (j != string::npos) {
      v.push_back(s.substr(i, j-i));
      i = ++j;
      j = s.find(c, j);

      if (j == string::npos)
         v.push_back(s.substr(i, s.length()));
   }
   return v;
}

int rep_write(char *rx);
void log_write(char *rx);
void idlog_write(int id, char *rx);

class Node {
  public:
    int id;
    string topic;
    vector<string> fields;

string getFieldTopic(int fieldno) {
  if(fieldno>=0 && fieldno < fields.size() ) {
    if(fields[fieldno] == "") return "";
    return topic + "/" + fields[fieldno];
  }else{ 
    return topic + "/" + to_string(fieldno+1);
  }
}
};

Node node[256];
string topic_prefix;

//######################################################
// Message 
//######################################################
class Message {
  public:
    int rssi;
    uint8_t buf[RH_RF69_MAX_MESSAGE_LEN+5];
    uint8_t buflen;
    char rx[RH_RF69_MAX_MESSAGE_LEN+5];
    int id;
    int val[RH_RF69_MAX_MESSAGE_LEN+5];
    int vallen;
    void process(RH_RF69 *r);
    void mqtt_write();
};

void Message::process(RH_RF69 *r) {
  buflen  = sizeof(buf)-5;
  if (r->recv(buf+4, &buflen)) {
    //reconstruct buffer
    buflen+=4;
    buf[0]=r->headerTo();
    buf[1]=r->headerFrom();
    buf[2]=r->headerId();
    buf[3]=r->headerFlags();
    buf[buflen]=0; //terminate string

    //convert buffer into text string
    for(int i=0;i<buflen;i++) rx[i] = (buf[i]=='\t' || (buf[i]>=32 && buf[i]<=127) ? buf[i]: '.');
    rx[buflen] = 0;

    //extract values and id
    val[0] = 0;
    vallen = str2arr(rx, "\t", val);
    id = val[0];

    //rssi
    rssi = r->lastRssi();

    //log it
    rep_write(rx);
    log_write(rx);
    idlog_write(id,rx);

    //verbose output
    fprintf(stdout,"RX\t");
    fprintDate(stdout);
    fprintf(stdout,"%d\t%s\n",rssi, rx);

    //mosquitto output
    mqtt_write();
  } else {
    printf("ERR receive failed");
  }
}

void Message::mqtt_write() {
  if(id<0||id>=256) return;
  printf("PUB\t%s\t%d\n",(node[id].topic+"/id").c_str(),id);
  printf("PUB\t%s\t%d\n",(node[id].topic+"/rssi").c_str(),rssi);
  for(int i = 1;i<vallen;i++) {
    string topic = node[id].getFieldTopic(i-1);
    if(topic!="") printf("PUB\t%s\t%d\n",topic.c_str(),val[i]);
  }
};

Message msg;

//######################################################
// COMBINED LOG FILE
//######################################################
#define NOVALUE -999999

//from ini file
bool rep_enable;
bool rep_stdout;
string rep_file;
string rep_items;
int rep_interval;

bool rep_gotval = false;
time_t rep_minute = getMinute();
int rep[1024];
int rep_cnt;
int rep_val[1024];
FILE* rep_f;

void rep_clear() {
  for(int i=0;i<100;i++) rep_val[i] = NOVALUE;
  rep_gotval = false;
}

void rep_init() {
  rep_clear();
  rep_cnt = str2arr(rep_items.c_str(), ",", rep);
  if(rep_enable) rep_f = fopen(rep_file.c_str(),"a");
}

void rep_print(FILE* f) {
  if(!rep_gotval) return;

  //print report values
  fprintf(f, "%d",rep_minute);

  char datestr[32];
  time_t tim = rep_minute;
  strftime(datestr, 32, "%Y-%m-%d %H:%M:%S", localtime(&tim));
  fprintf(f, "\t");
  fprintf(f, datestr);

  for(int i=0;i<rep_cnt/2;i++) {
    fprintf(f, "\t");
    if(rep_val[i]!=NOVALUE) fprintf(f, "%d", rep_val[i]);
  }

  fprintf(f, "\n");
  fflush(f);
}

int rep_write(char* rx) {
  int cur_minute = getMinute();
  if( (cur_minute / rep_interval) != (rep_minute / rep_interval)) {
    if(rep_enable) rep_print(rep_f);
    if(rep_stdout) rep_print(stdout);
    rep_clear();
    rep_minute = cur_minute;
  }

  //add values to report values
  int val[1024];
  val[0] = 0;
  int vallen = str2arr(rx, "\t", val); 
  for(int i=0;i<rep_cnt;i+=2) {
    if(rep[i] == val[0] && rep[i+1] < vallen) {
      rep_val[i/2] = val[ rep[i+1] ];
      rep_gotval = true;
    }
  }

  return val[0];
}

//######################################################
// LOG FILE
//######################################################
bool log_enable;
string log_file;
FILE* log_f;

void log_init() {
  if(!log_enable) return;
  log_f = fopen(log_file.c_str(),"a");
}

void log_write(char *rx) {
  if(!log_enable) return;
  fprintDate(log_f);
  fprintf(log_f,"%d\t%s\n", (int)rf69.lastRssi(), rx);
  fflush(log_f);
}

void log_close() {
  if(!log_enable) return;
  fclose(log_f);
}

//########################################################
// Log separate file per ID
//########################################################
bool idlog_enable;

void idlog_write(int id, char *rx) {
  if(!idlog_enable) return;
  char fn[100];
  sprintf(fn,"%d.txt",id);
  FILE *f = fopen(fn,"a");
  fprintDate(f);
  fprintf(f,"%d\t%s\n", (int)rf69.lastRssi(), rx);
  fflush(f);
  fclose(f);
}

//########################################################
// MAIN
//########################################################
#define RF_CS_PIN  RPI_V2_GPIO_P1_24 // Slave Select on CE0 so P1 connector pin #24

// Our RFM69 Configuration 
#define RF_FREQUENCY  868.00

// Create an instance of a driver
RH_RF69 rf69(RF_CS_PIN);

//Flag for Ctrl-C
volatile sig_atomic_t force_exit = false;

void sig_handler(int sig)
{
//  newEvent(); printEvent("INFO", "%s Break received, exiting!", __BASEFILE__);
  force_exit=true;
}

int main (int argc, const char* argv[] )
{ 
  //disable buffering on stdout
  setbuf(stdout, NULL);

  //read ini file
  INIReader ini(INIFILE);

  if(ini.ParseError() < 0 ) {
    printf("error reading ini file '%s'\n",INIFILE);
    return 2;
  }

  rep_enable   = ini.GetBoolean("","rep_enable",   false);
  rep_stdout   = ini.GetBoolean("","rep_stdout",   false);
  rep_items    = ini.GetString ("","rep_items",    "");
  rep_file     = ini.GetString ("","rep_file",     "");
  rep_interval = ini.GetInteger("","rep_interval", 1);

  log_enable   = ini.GetBoolean("","log_enable",   false);
  log_file     = ini.GetString ("","log_file",     "");

  idlog_enable = ini.GetBoolean("","idlog_enable", false);

  printf("log_file=%s idlog=%d rep_interval=%d\n",log_file.c_str(), idlog_enable, rep_interval);

  topic_prefix =  ini.GetString ("","topic_prefix",     "");

  //node definitions
  for(int i=0;i<256;i++) {
    node[i].id = i;
    node[i].topic = topic_prefix + "/" + ini.GetString("node"+to_string(i),"topic",to_string(i));
    node[i].fields = split(ini.GetString("node"+to_string(i),"fields",""), ","); 
  }

  //init loggers
  log_init();
  rep_init();

  printf("REP=");
  for(int i=0;i<rep_cnt;i++) printf("%d,",rep[i]);
  printf("\n");

  //init bcm2835
  unsigned long rx_millis = 0;
  
  signal(SIGINT, sig_handler);
  printf( "INFO starting %s\n", __BASEFILE__);

  if (!bcm2835_init()) {
    printf( "ERR %s bcm2835_init() Failed\n", __BASEFILE__ );
    return 1;
  }
  
  //init rf69
  printf( "INFO CS=GPIO%d\n", RF_CS_PIN);

  if (!rf69.init()) {
    printf( "ERR RF69 module init failed, Please verify wiring/module\n" );
  } else {
  //  newEvent(); printEvent( "INFO", "RF69 module init OK!");
    
    // Defaults after init are 434.0MHz, +13dBm, no encryption, GFSK=250kbps, Fdev=250kHz

    // The default transmitter power is 13dBm. If you are using
    // High power version (RFM69HW or RFM69HCW) you need to set 
    // transmitter power to at least 14 dBm up to 20dBm
    rf69.setTxPower(20); 

    // Now we change back to Moteino setting to be 
    // compatible with RFM69 library from lowpowerlabs 
 ///   rf69.setModemConfig( RH_RF69::FSK_MOTEINO);

    // sync words default 0x2d 0xd4
    uint8_t syncwords[2];
    syncwords[0] = 0x2d;
    syncwords[1] = 0xd4;
    rf69.setSyncWords(syncwords, sizeof(syncwords));

    // Adjust Frequency
    rf69.setFrequency( RF_FREQUENCY );

    // Be sure to grab all node packet 
    // we're sniffing to display, it's a demo
    rf69.setPromiscuous(true);

    // We're ready to listen for incoming message
    rf69.setModeRx();

    printf( "INFO Sync=%02x%02x, %3.2fMHz\n", syncwords[0], syncwords[1], RF_FREQUENCY );

    //main loop
    while (!force_exit) {
      if (rf69.available()) msg.process(&rf69);
        
      // Let OS do other tasks
      // For timed critical appliation you can reduce or delete
      // this delay, but this will charge CPU usage, take care and monitor
      bcm2835_delay(5);
    }
  }

  bcm2835_close();
  log_close();
  return 0;
}

