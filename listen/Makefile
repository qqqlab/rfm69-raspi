# Makefile
# Sample for RH_RF69 (client and server) on Raspberry Pi
# Caution: requires bcm2835 library to be already installed
# http://www.airspayce.com/mikem/bcm2835/

CC            = g++
CFLAGS        = -DRASPBERRY_PI -DBCM2835_NO_DELAY_COMPATIBILITY -D__BASEFILE__=\"$*\"
LIBS          = -lbcm2835
RADIOHEADBASE = ../lib/RadioHead
INCLUDE       = -I$(RADIOHEADBASE)
LIBBASE       = ../lib
MAIN          = listen
OUTPATH       = ../bin

all: ${MAIN}
	mv $(MAIN) $(OUTPATH) 

RasPi.o: $(RADIOHEADBASE)/RHutil/RasPi.cpp
	$(CC) $(CFLAGS) -c $(RADIOHEADBASE)/RHutil/RasPi.cpp $(INCLUDE)

${MAIN}.o: ${MAIN}.cpp
	$(CC) $(CFLAGS) -c $(INCLUDE) $<

RH_RF69.o: $(RADIOHEADBASE)/RH_RF69.cpp
	$(CC) $(CFLAGS) -c $(INCLUDE) $<

RHDatagram.o: $(RADIOHEADBASE)/RHDatagram.cpp
	$(CC) $(CFLAGS) -c $(INCLUDE) $<

RHHardwareSPI.o: $(RADIOHEADBASE)/RHHardwareSPI.cpp
	$(CC) $(CFLAGS) -c $(INCLUDE) $<

RHSPIDriver.o: $(RADIOHEADBASE)/RHSPIDriver.cpp
	$(CC) $(CFLAGS) -c $(INCLUDE) $<

RHGenericDriver.o: $(RADIOHEADBASE)/RHGenericDriver.cpp
	$(CC) $(CFLAGS) -c $(INCLUDE) $<

RHGenericSPI.o: $(RADIOHEADBASE)/RHGenericSPI.cpp
	$(CC) $(CFLAGS) -c $(INCLUDE) $<

INIReader.o: ${LIBBASE}/inih/cpp/INIReader.cpp
	$(CC) $(CFLAGS) -c $(INCLUDE) $<

ini.o: ${LIBBASE}/inih/ini.c
	$(CC) $(CFLAGS) -c $(INCLUDE) $<

${MAIN}: ${MAIN}.o INIReader.o ini.o RH_RF69.o RasPi.o RHHardwareSPI.o RHGenericDriver.o RHGenericSPI.o RHSPIDriver.o
	$(CC) $^ $(LIBS) -o ${MAIN}

clean:
	rm -rf *.o ${MAIN}
