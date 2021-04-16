#!/usr/bin/env python3

import cv2
import numpy as np

import os
import time
import multiprocessing

import paho.mqtt.publish as publish
import ssl

'''
Chefcito - versión fase 2

Algoritmo:
    - Se abre la cámara de video:
        -   captura del frame de video:
        -   identificacion de objetos en el primer plano de la fotografía
        -   segmentación de objetos en la fotografía (alimentos no se deben tocar)
        -   clasificación de objetos en la fotografía
        -   conteo de alimentos
        -   calculo de calorias
        -   envio de datos:
                {kCal totales, # Aguacates, # Bananos, # Huevos, # Manzanas, # Salchichas, # Hora y fecha (nombre del archivo de la imagen)}

'''

# Se crea una funcion para clasificar cada contorno en un proceso aislado, esto con el fin de que el sistema mate el proceso
# externo una vez se haya realizado la inferencia, eliminando de la memoria las variables alojadas como tensores del modelo
def modelo_inferencia(roi, out):

    import tflite_runtime.interpreter as tflite

    # Se carga el modelo de inferencia entrenado previamente
    model = tflite.Interpreter(model_path="chefcito_model.tflite")
    model.allocate_tensors()

    # Se obtienen los tensores de entrada y salida que alojaran la entrada a inferir y su salida
    input_details = model.get_input_details()
    output_details = model.get_output_details()

    # Se sobremuestrea la imagen a las dimensiones de entrada del modelo de inferencia
    roi = cv2.resize(roi, (224,224), interpolation=cv2.INTER_CUBIC)

    # Se agrega una cuarta dimension a la imagen: dimension del batch.
    input_img = np.expand_dims(roi, axis = 0)
    # Se normaliza entre [0,1]
    input_img = np.float32(input_img) / 255.0
    #input_img = np.float32(input_img)

    # Se aloja la imagen 'roi' en el tensor de entrada del modelo
    model.set_tensor(input_details[0]['index'], input_img)
    # Se invoca el modelo en memoria para ser ejecutado por la CPU
    model.invoke()

    # Se obtiene el tensor de salida inferido respecto a la region en interes de entrada, el tensor de salida
    # tiene forma [[a,b,c,d,...]] donde cada valor es la probabilidad normalizada respecto al resto de valores
    # (la suma de todos los valores da 1), de pertenecer a la clase referida a la posicion del valor.
    # El tensor de salida tiene 'n' valores, siendo 'n' el numero de clases posibles.
    output_data = model.get_tensor(output_details[0]['index'])
    resultado = np.squeeze(output_data) # Se elimina la dimension exterior del vector de salida, quedando [a,b,c,...]
    out.put(resultado) # Se escribe un valor en memoria sobre la variable FIFO que se puso en espera para el proceso aislado

# Se crea una funcion para filtrar las sombras de los objetos segmentados
def filtrar_sombras(img, tresh=15):

    imghsv = cv2.cvtColor(img, cv2.COLOR_BGR2HSV)
    _,_,v = cv2.split(imghsv) # Se evalua el valor medio del canal de intensidad

    if np.mean(v) > tresh:
        return True
    else:
        return False

#https://www.mathworks.com/help/thingspeak/use-raspberry-pi-board-that-runs-python-websockets-to-publish-to-a-channel.html
# ID del canal de ThingSpeak para publicar los datos
channelID = "1357298"
# Clave de escritura de la API del canal de ThingSpeak
writeAPIKey = "G8HYAAXDN3CTKU5B"

# Comunicacion utilizando websocket en el puerto por defecto 80
tTransport = "websockets"
tPort = 80 #443
#tTLS = {"ca_certs":"/etc/ssl/certs/ca-certificates.crt","tls_version":ssl.PROTOCOL_TLSv1} # Comunicacion utilizando TLS (protocolo SSl) para encriptar los datos
tTLS = None

# Topic para publicar en el Broker
topic = "channels/" + channelID + "/publish/" + writeAPIKey
mqttHost = "mqtt.thingspeak.com"

# Cualquier nombre de usuario
mqttUsername = "TSChefcito"

# Llave de la API MQTT
mqttAPIKey = "6R8D8X8I1VX6MG2Z"

# Se abre un objeto para controlar la unica camara web conectada,
#listada como el dispositivo 0 en sistemas Linux
cap = cv2.VideoCapture(0)

print("Oprima -f- para capturar y procesar el frame \nMantenga oprimido -q- para salir del programa")
while(True):

    # Se captura el frame actual de la camara
    ret, frame = cap.read()
    if ret == False: break # Si la bandera es falsa, entonces algo esta mal con el dispositivo

    cv2.imshow('frame', frame) # Se muestra el stream de video en pantalla

    # Si se oprime la tecla 'f', se procesa el frame actual
    if cv2.waitKey(1) & 0xFF == ord('f'):

        print("\n\nProcesando frame actual...")
        cv2.destroyAllWindows()

        # El siguiente algoritmo clasificara los alimentos dentro de la imagen capturada:
        # 1. Se correra un algoritmo de clusterizacion para separar el fondo del primer plano
        # 2. Cada contorno en el primer plano sera aislado con el fin de aplicar el modelo de inferencia
        # de aprendizaje de maquina entrenado, la salida de este modelo indicara la clase a la que pertence
        # el objeto segmentado: aguacate, banano, huevo, mandarina, manzana, naranja o salchicha
        # 3. Se guardara el frame capturado con los alimentos segmentados impresos sobre la imagen

        # Pre-procesamiento del frame capturado
        datetime = time.strftime("%Y%m%d%H%M%S", time.localtime())
        #frame = cv2.imread('comida.jpg')
        frame = cv2.resize(frame, (600, 600), interpolation=cv2.INTER_AREA)
        img = frame.copy()

        # 1. Aplicacion de K-medios para separar el primer plano del segundo plano:
        pixel_vals = frame.reshape((-1, 3)) # Se redimensiona el frame capturado para obtener una lista de puntos para cada canal de color
        pixel_vals = np.float32(pixel_vals)

        criteria = (cv2.TERM_CRITERIA_EPS + cv2.TERM_CRITERIA_MAX_ITER, 100, 0.95) # Criterio para terminar la ejecucion del algoritmo k-means: 100 iteraciones, exactitud del 0.95
        k = 2 # Numero de clusteres: primer y segundo plano

        # Ejecucion del algoritmo K-medias en OpenCV, el algoritmo se ejecuta 10 veces  con inicializaciones
        # distintas para los dos centroides, se  retorna el  intento con mejor compacticidad entre los clusteres
        # retval -> mejor compactividad encontrada entre los centroides y el conjunto de datos
        # labels -> la etiqueta que le corresponde a cada pixel; 0 o 1 (2 clusteres)
        # centers -> los valores de intensidad que definen los dos centroides para cada canal de color
        retval, labels, centers = cv2.kmeans(pixel_vals, k, None, criteria, 10, cv2.KMEANS_RANDOM_CENTERS)

        centers = np.uint8(centers)
        segmented_data = centers[labels.flatten()] # Se segmenta cada pixel de acuerdo a su intensidad (centroide) correspondida
        segmented_image = segmented_data.reshape((frame.shape)) # Se redimensiona de nuevo a las dimensiones del frame capturado
        cv2.imshow("frame segmentado en 2 clusteres", segmented_image)

        segmented_image = cv2.cvtColor(segmented_image, cv2.COLOR_BGR2GRAY) # Se convierte el frame segmentado a escala de grises
        # Se umbraliza la imagen segmentada de acuerdo al Metodo de Otsu, el cual selecciona el punto de umbralizacion
        # como la interseccion entre las dos colas de las curvas probabilisticas que describen el histograma de la
        # imagen, al ser una imagen bimodal (primer plano y segundo plano segmentado), este es el mejor metodo para umbralizar
        _, mask = cv2.threshold(segmented_image, 0, 255, cv2.THRESH_BINARY | cv2.THRESH_OTSU)
        mask = cv2.bitwise_not(mask) # Se invierte la imagen umbralizada; primer plano pasa a representarse con 255, segundo plano pasa a ser 0
        mask = cv2.morphologyEx(mask, cv2.MORPH_CLOSE, cv2.getStructuringElement(cv2.MORPH_ELLIPSE, (3,3)), iterations=3)
        cv2.imshow("Mascara binaria de objs en primer plano", mask)

        cv2.waitKey(0)
        cv2.destroyAllWindows()

        # Se encuentran los contornos en la imagen umbralizada (no se consideran los contornos
        # internos dentro de cada objeto). Cada contorno se representa con una lista de puntos,
        # cada punto es una coordenada espacial dentro de la imagen segmentada
        contours, _ = cv2.findContours(mask, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)

        # 2. Se procede a segmentar y clasificar cada contorno:
        # Se cargan las etiquetas con las clases de clasificacion
        with open('labels.txt', 'r') as f:
            labels = [line.strip() for line in f.readlines()]
            
        outputs = []  # Lista que almacenara la clase predicha para cada contorno
        cont = 1
        for contour in contours:

            # Se calcula el minimo rectangulo contenedor del contorno,
            # la funcion utiliza los momentos de imagen para este fin (simil fisico al momento de inercia de un objeto, sino que en vez de masa, se tiene cantidad de intensidad)
            x,y,w,h = cv2.boundingRect(contour)

            # Se genera una mascara binaria de acuerdo al contorno hallado
            mask = np.zeros(frame.shape[0:2], dtype=np.uint8)
            mask = cv2.drawContours(mask, [contour], -1, 255, thickness=-1)
            # Se aplica la mascara binaria sobre el frame capturado
            res = cv2.bitwise_and(frame, frame, mask=mask)

            # Se segmenta la region en interes del frame capturado
            ROI = res[y:y+h, x:x+w]
            flag = filtrar_sombras(ROI) # Se filtran las sombras en los objetos segmentados

            # El area del minimo rectangulo contenedor debe ser mayor a un valor (1000 pixeles cuadrados), ademas que no debe ser una sombra
            # Hiper-parametro de decision para filtrar contornos ruidosos
            if (flag == True) and (w*h > 1000):
                # Se agregan ceros en los bordes de la imagen ROI con el fin de obtener una imagen cuadrada
                if ROI.shape[0] > ROI.shape[1]:
                    add = (ROI.shape[0]-ROI.shape[1]) // 2
                    ROI = cv2.copyMakeBorder(ROI,0,0,add,add,cv2.BORDER_CONSTANT,value=(0,0,0))
                else:
                    add = (ROI.shape[1]-ROI.shape[0]) // 2
                    ROI = cv2.copyMakeBorder(ROI,add,add,0,0,cv2.BORDER_CONSTANT,value=(0,0,0))
                # Se dibuja el contorno sobre el frame capturado
                cv2.drawContours(img, [contour], -1, (0,0,255), thickness=2)

                print("Para el contorno %d " % (cont))

                # Se define una variable FIFO que se pondra en espera para recibir el resultado del proceso aislado
                out = multiprocessing.Queue()
                # Se define el proceso aislado a inicializar
                process_eval = multiprocessing.Process(target=modelo_inferencia, args=(ROI,out))
                # Se inicializa el proceso aislado
                process_eval.start()
                # Se redirecciona una variable en memoria para obtener el resultado de la variable en espera definida
                resultado = out.get()
                # Se espera a que se termine el proceso aislado
                process_eval.join()

                # Se crea una nueva lista que almacena las posiciones de acuerdo a las probabilidades en 'resultado' en orden de mayor a menor
                top_pred = resultado.argsort()[-7:][::-1] # ejem: resultado=[0.2, 0.5, 0.3] -> top_pred=[1,2,0]
                outputs.append(labels[top_pred[0]]) # se guarda la clase predicha para el contorno actual
                for i in top_pred:
                    # Se imprime el resultado obtenido en la inferencia, con su respectiva etiqueta
                    print('%04f: %s' % (float(resultado[i]), labels[i]))
                cont += 1

                # 3. Se imprime la prediccion sobre el frame capturado
                cv2.putText(img, labels[top_pred[0]], (x, y), cv2.FONT_HERSHEY_SIMPLEX, 1, (255,0,0), 3)
            else:
                pass # Si el contorno no cumple con la condicion, se salta ya que solo encierra ruido

        cv2.imshow('Alimentos Clasificados', img)
        cv2.waitKey()
        cv2.destroyAllWindows()

        imgfile = "{}.jpg".format(datetime) # Se crea el nombre del archivo de la imagen a guardar

        kCal = {'aguacate':160, 'banano':122, 'huevo':105, 'manzana':52, 'salchicha':346}
        kcals = [kCal.get(i) for i in outputs] # Se mapean los resultados con sus calorias correspondientes

        # Se imprime en consola la siguiente informacion
        print("\nFecha de la captura de: {}-{}-{}. Hora de la captura: {}:{}:{} ".format(datetime[0:4],datetime[4:6],datetime[6:8],datetime[8:10],datetime[10:12],datetime[12:14]))
        print("Imagen guardada: {}".format(imgfile))
        print("Alimentos consumidos: {} ".format(outputs))
        print("Calorias por alimento: {} kCal ".format(kcals))
        print("Total calorias consumidas: {} kCal.".format(np.sum(kcals)))

        # Variables para transmitir en el canal de thingspeak
        contAguacate = 0
        contBanano = 0
        contHuevo = 0
        contManzana = 0
        contSalchicha = 0
        while len(outputs)!=0:
            aux = outputs.pop()
            if aux == 'aguacate':
                contAguacate += 1
            elif aux == 'banano':
                contBanano += 1
            elif aux == 'huevo':
                contHuevo += 1
            elif aux == 'manzana':
                contManzana += 1
            elif aux == 'salchicha':
                contSalchicha += 1

        # Payload: cadena con los datos a publicar en el topic del broker
        tPayload = "field1="+str(np.sum(kcals))+"&field2="+str(contAguacate)+"&field3="+str(contBanano)+"&field4="+str(contHuevo)+"&field5="+str(contManzana)+"&field6="+str(contSalchicha)+"&field7="+str(datetime)

        # Intento de publicacion los datos usando MQTT
        try:
            print("\nIniciando transmision MQTT hacia ThingSpeak...")
            publish.single(topic, payload=tPayload, hostname=mqttHost, transport=tTransport, port=tPort, tls=tTLS, auth={'username':mqttUsername,'password':mqttAPIKey})
            print("Hecho")
        except (KeyboardInterrupt):
            break
        except:
            print("Error publicando los datos")

        cv2.imwrite(imgfile, img) # Se guarda localmente la imagen con la clasificacion
        # Intento de publicacion de la imagen clasificada en el deposito de Google Cloud
        try:
            print("\nSubiendo imagen {} al servicio de Google Cloud".format(imgfile))
            #https://cloud.google.com/storage/docs/quickstart-gsutil
            os.system("gsutil cp {} gs://mi_primer_deposito".format(imgfile))
            print("Hecho")
            os.remove(imgfile) # Se elimina la imagen almacenada localmente
        except:
            print("Error publicando la imagen")
        
        print("\n\nOprima -f- para capturar y procesar el frame \nMantenga oprimido -q- para salir del programa")
        
    # Si se oprime la tecla 'q', se cierra el programa
    elif cv2.waitKey(1) & 0xFF == ord('q'):
        cv2.destroyAllWindows()
        print("\n\nSaliendo...")
        break

cap.release() # Se cierra el objeto que controla la camara webqqqqq