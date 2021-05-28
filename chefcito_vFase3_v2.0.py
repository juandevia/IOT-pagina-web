#!/usr/bin/env python3

import cv2
import numpy as np

import sys
import os
import time
import multiprocessing

import paho.mqtt.publish as publish
import requests
import json

'''
Chefcito RELOAD (⌐■_■) - versión fase 3
Algoritmo:
 
        -¿Se solicita capturar una fotografía?
            
            - No -> esperar
            
            - Si ->:
                -   captura del frame de video:
                -   identificacion del plato con los alimentos
                -   segmentación de alimentos (alimentos no se deben tocar)
                -   clasificación de alimentos
                -   conteo de alimentos
                -   calculo de calorias
                -   envio de datos:
                        {kCal totales, # Aguacates, # Bananos, # Huevos, # Manzanas, # Salchichas, # Hora y fecha (nombre del archivo de la imagen)}
'''


'''------------------------------ FUNCIONES ----------------------------------------------------'''
# Funcion para segmentar la region del plato que contiene los alimentos
def ROI_platodecomida(frame):
    
    # Se convierte la imagen capturada a escala de grises para umbralizarla
    gimg = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)        
    _, mask = cv2.threshold(gimg, 0, 255, cv2.THRESH_BINARY | cv2.THRESH_OTSU)
    mask = cv2.bitwise_not(mask)
    mask = cv2.morphologyEx(mask, cv2.MORPH_CLOSE, cv2.getStructuringElement(cv2.MORPH_ELLIPSE, (3,3)), iterations=3)
    
    # Se identifican todos los contornos en la imagen  
    contours, _ = cv2.findContours(mask, cv2.RETR_LIST, cv2.CHAIN_APPROX_SIMPLE)
    outercnt = []

    # Se filtran los 2 contornos mas grandes
    n = 0
    while n < 2:
        maxArea = 0
        for cnt in contours:
            area = cv2.contourArea(cnt)
            if area > maxArea:
                maxArea = area
                maxCnt = cnt
        outercnt.append(maxCnt)
        cleancnt = [x for x in contours if x is not maxCnt]
        contours = cleancnt
        n += 1
    
    # De los 3 contornos encontrados, se filtran aquellos perfectamente circulares
    foodplatecnt = []
    for cnt in outercnt:
        area = cv2.contourArea(cnt)
        hull = cv2.convexHull(cnt)
        hull_area = cv2.contourArea(hull)
        solidity = float(area)/hull_area
        if solidity >= 0.99: # la solidez de un circulo es >0.99
            foodplatecnt.append(cnt)
    
    # Se identifica el contorno circular de menor area
    if len(foodplatecnt) == 0:
        print("No se identifico el borde del plato... Capture la foto de nuevo")
        return False
        #sys.exit("ahhh!! Errores, no se identifica el borde del plato")
    elif len(foodplatecnt) > 1: # Si hay mas de un posible contorno de plato, se elgie el de menor area
        aux = []
        minArea = 1e10
        for cnt in foodplatecnt:
            area = cv2.contourArea(cnt)
            if area < minArea:
                minArea = area
                minCnt = cnt
        aux.append(minCnt)
        foodplatecnt = aux
    
    # Se crea una mascara binaria con la ROI que encierra el plato
    x,y,w,h = cv2.boundingRect(foodplatecnt[0])
    mask = np.zeros((frame.shape[0], frame.shape[1], 1), dtype=np.uint8)
    cv2.drawContours(mask, foodplatecnt, -1, 255, thickness=cv2.FILLED)    
    
    #cv2.imshow("mascara1", mask)
    #cv2.waitKey(0)
    #cv2.destroyAllWindows()
    # Se quita la linea del plato
    mask = cv2.erode(mask, cv2.getStructuringElement(cv2.MORPH_ELLIPSE, (3,3)), iterations=3)
    #cv2.imshow("mascara2", mask)
    #cv2.waitKey(0)
    #cv2.destroyAllWindows()    
    
    # Se aplica la mascara binaria sobre el frame capturado
    img = cv2.bitwise_and(frame, frame, mask=mask)
    nmask = cv2.bitwise_not(mask)
    bmask = nmask
    bmask[bmask == 255] = 123
    gmask = nmask
    gmask[gmask == 255] = 135
    nmask = cv2.merge((bmask,gmask,bmask))
    img = cv2.add(img, nmask)
    contours, _ = cv2.findContours(mask, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
    x,y,w,h = cv2.boundingRect(contours[0])
    img = img[y:y+h, x:x+w]
    #cv2.imshow("img", img)
    #cv2.waitKey(0)
    #cv2.destroyAllWindows()
    
    return img

# Se crea una funcion para clasificar cada contorno en un proceso aislado
def modelo_inferencia(roi, out):
    
    # Cuaderno de google colab para entrenar a Chefcito: https://colab.research.google.com/drive/1fnLDLyTuZ8qJfhXhdqGeaVAyhGiqWA2y?usp=sharing
    # Se importa el modelo de ingerencia y se aloja en memoria sus tensores
    import tflite_runtime.interpreter as tflite
    model = tflite.Interpreter(model_path="chefcito_model.tflite")
    model.allocate_tensors()
    
    # Se obtienen los detalles de los tensores de entrada y salida del modelo de inferencia
    input_details = model.get_input_details()
    output_details = model.get_output_details()
    
    # Se adecuan las dimensiones de la imagen de entrada y se normaliza entre [0,1]
    roi = cv2.resize(roi, (224,224), interpolation=cv2.INTER_CUBIC)
    input_img = np.expand_dims(roi, axis = 0)
    input_img = np.float32(input_img) / 255.0
    
    # Se inserta la imagen de entrada en los tensores de entrada del
    # modelo, luego se invoca el modelo en memoria para ser ejecutado en la CPU
    model.set_tensor(input_details[0]['index'], input_img)
    model.invoke()
    
    # Se obtiene el tensor de salida inferido respecto a la region en interes de entrada, el tensor de salida
    # tiene forma [[a,b,c,d,...]] donde cada valor es la probabilidad normalizada respecto al resto de valores
    # (la suma de todos los valores da 1), de pertenecer a la clase referida a la posicion del valor.
    # El tensor de salida tiene 'n' valores, siendo 'n' el numero de clases posibles.
    output_data = model.get_tensor(output_details[0]['index'])
    # Se elimina la dimension exterior del vector de salida, quedando [a,b,c,...]
    resultado = np.squeeze(output_data) 
    out.put(resultado) # Se escribe la variable FIFO que se puso en espera para el proceso aislado creado

# Se crea una funcion para filtrar las sombras de los objetos segmentados
def filtrar_sombras(img, tresh=5):
    
    # Se filtra el canal de valor o intensidad en el espacio de color HSV
    imghsv = cv2.cvtColor(img, cv2.COLOR_BGR2HSV)
    _,_,v = cv2.split(imghsv)
    if np.mean(v) > tresh:
        return True
    else:
        return False



'''------------------------- MAIN -----------------------------'''

# Caracteristicas del canal MQTT para publicar datos
mqttHost = "mqtt.thingspeak.com"
mqttUsername = "TSChefcito"
mqttAPIKey = "6R8D8X8I1VX6MG2Z"
tTransport = "websockets"
tPort = 80
tTLS = None

# Bandera para encender la camara
flag = 0

while (1):
    
    # Switch Case del algoritmo
    if flag == 0:
        
        # Canal ThingSpeak para saber si se solicita tomar la foto
        channelID = "1363744"
        writeAPIKey = "CELL2W7CXZIZ6DV9"
        topic = "channels/" + channelID + "/publish/" + writeAPIKey
        
        try:
            # Se debe bajar la bandera a '0' antes de salir del bucle
            tPayload = "field1="+str(0)
            print("Bajando la bandera de ThingSpeak...")
            publish.single(topic, payload=tPayload, hostname=mqttHost, transport=tTransport, port=tPort, tls=tTLS, auth={'username':mqttUsername,'password':mqttAPIKey})
            print("Hecho")
        except:
            print("Error bajando la bandera de ThingSpeak")   
    
        # Bucle para leer la bandera de captura de foto
        time.sleep(5)
        print("\n\nEsperando a que se solicite capturar una fotografia...")
        while(1):
            
            time.sleep(2)
            # Se solicitan los datos del field1 del canal de ThingSpeak
            url = "https://api.thingspeak.com/channels/1363744/fields/1.json?api_key=XL5GE2KFGKFQL003&results=1"
            x = requests.get(url)
            
            # El metodo GET retorna un formulario .json con la informacion del canal
            x_dict = json.loads(x.text)
            #for i in x_dict: print("key: ", i, "val :", x_dict[i]) # Imprimir las llaves del diccionario
            flag = int(x_dict['feeds'][0]['field1'])
            
            # Si la bandera es '1', es porque se ha solicitado tomar una foto
            if flag == 1:
                print("CONFIRMACION: se ha solicitado capturar una fotografia")
                try:
                    # Se debe bajar la bandera a '0' antes de salir del bucle
                    tPayload = "field1="+str(0)
                    print("Bajando la bandera de ThingSpeak...")
                    publish.single(topic, payload=tPayload, hostname=mqttHost, transport=tTransport, port=tPort, tls=tTLS, auth={'username':mqttUsername,'password':mqttAPIKey})
                    print("Hecho")
                except:
                    print("Error bajando la bandera de ThingSpeak")   
                break

    elif flag == 1:

        print("\nSe procede a capturar la fotografia y a procesarla...")
        flag = 0        
        
        # https://www.mathworks.com/help/thingspeak/use-raspberry-pi-board-that-runs-python-websockets-to-publish-to-a-channel.html
        # Canal ThingSpeak para publicar los datos sensados 
        channelID = "1357298"
        writeAPIKey = "G8HYAAXDN3CTKU5B"
        topic = "channels/" + channelID + "/publish/" + writeAPIKey

        # Se cargan las etiquetas con las clases de clasificacion de alimentos
        with open('labels.txt', 'r') as f:
            labels = [line.strip() for line in f.readlines()]

        # Se abre la camara y se toman 10 frames; se utiliza el ultimo
        cap = cv2.VideoCapture(0)
        for i in range(10):
            # Se captura el frame actual de la camara y se verifica que si sea una imagen
            ret, frame = cap.read()
            if ret == False:      
                print('No se puede abrir la camara.... RIP')
                sys.exit("ahhh!! Errores, no se pudo abrir la camara")
        cap.release() # Se cierra el objeto que controla la camara

        # Timestamp - Nombre de la imagen procesada a guardar en la nube
        datetime = time.strftime("%Y%m%d%H%M%S", time.localtime())
                
        # Se ubica la region en interes de la foto; la que encierra a los alimentos
        fmask = ROI_platodecomida(frame)
        img = fmask.copy()
        
        # Se clusterizan los colores de la imagen a partir del filtrado un piramidal utilizando
        # la tecnica de clustering "mean shift" (busca el maximo local de una funcion de distribucion)
        fmask = cv2.cvtColor(fmask, cv2.COLOR_BGR2HSV)
        fmask = cv2.pyrMeanShiftFiltering(fmask, 20, 30, 2)
        fmask = cv2.cvtColor(fmask, cv2.COLOR_HSV2BGR)
        
        # Se umbraliza la ROI con los alimentos para identificar los contornos internos
        gmask = cv2.cvtColor(fmask, cv2.COLOR_BGR2GRAY)
        #cv2.imshow("gris", gmask)
        #cv2.waitKey(0)
        #cv2.destroyAllWindows()
        _, gmask = cv2.threshold(gmask, 0, 255, cv2.THRESH_BINARY | cv2.THRESH_OTSU)
        #cv2.imshow("umbralizada", gmask)
        #cv2.waitKey(0)
        #cv2.destroyAllWindows()
        gmask = cv2.bitwise_not(gmask)
        gmask = cv2.morphologyEx(gmask, cv2.MORPH_CLOSE, cv2.getStructuringElement(cv2.MORPH_ELLIPSE, (3,3)), iterations=3)
        #cv2.imshow("umbralizada filtrada", gmask)
        #cv2.waitKey(0)
        #cv2.destroyAllWindows()
        
        # Se buscan los contornos dentro del plato de comida       
        contours, _ = cv2.findContours(gmask, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)

        # Se procede a segmentar y clasificar cada contorno:
        outputs = []  # Lista que almacenara la clase predicha para cada contorno
        cont = 1
        for contour in contours:
            
            # Se calcula el minimo rectangulo contenedor del contorno,
            # la funcion utiliza los momentos de imagen para este fin (simil fisico al momento de inercia de un objeto)
            x,y,w,h = cv2.boundingRect(contour)
            mask = np.zeros(fmask.shape[0:2], dtype=np.uint8)
            mask = cv2.drawContours(mask, [contour], -1, 255, thickness=-1)
            
            # Se aplica la mascara binaria sobre la imagen con los alimentos
            res = cv2.bitwise_and(fmask, fmask, mask=mask)
            
            # Se segmenta la region en interes del objeto que encierra el contorno
            ROI = res[y:y+h, x:x+w]
            flagg = filtrar_sombras(ROI)
            
            # El area del minimo rectangulo contenedor debe ser mayor a un valor (1000 pixeles cuadrados)
            if (flagg == True) and (w*h > 1000):
                
                # Se agregan ceros en los bordes de la imagen ROI con el fin de obtener una imagen cuadrada
                if ROI.shape[0] > ROI.shape[1]:
                    add = (ROI.shape[0]-ROI.shape[1]) // 2
                    ROI = cv2.copyMakeBorder(ROI,0,0,add,add,cv2.BORDER_CONSTANT,value=(0,0,0))
                else:
                    add = (ROI.shape[1]-ROI.shape[0]) // 2
                    ROI = cv2.copyMakeBorder(ROI,add,add,0,0,cv2.BORDER_CONSTANT,value=(0,0,0))
                
                # Se dibuja el contorno sobre el frame capturado
                cv2.drawContours(img, [contour], -1, (0,255,0), thickness=1)

                print("Para el contorno %d " % (cont))

                # Se define una variable FIFO que se pondra en espera para recibir el resultado del proceso aislado
                out = multiprocessing.Queue()
                
                # Se define el proceso aislado a inicializar
                process_eval = multiprocessing.Process(target=modelo_inferencia, args=(ROI,out))
                process_eval.start()
                resultado = out.get()
                
                # Se espera a que el proceso aislado termine
                process_eval.join()

                # Se crea una nueva lista que almacena las posicions de acuerdo al organizacion de las probabilidades de mayor a menor
                top_pred = resultado.argsort()[-7:][::-1] # ejem: resultado=[0.2, 0.5, 0.3] -> top_pred=[1,2,0]
                outputs.append(labels[top_pred[0]]) # se guarda la clase predicha para el contorno actual
                for i in top_pred:
                    
                    # Se imprime el resultado obtenido en la inferencia, con su respectiva etiqueta
                    print('%04f: %s' % (float(resultado[i]), labels[i]))
                
                cont += 1
                # 3. Se imprime la prediccion sobre el frame capturado
                cv2.putText(img, labels[top_pred[0]], (x, y), cv2.FONT_HERSHEY_SIMPLEX, 1, (128,0,128), 1)
            
            else:
                
                pass # Si el contorno no cumple con la condicion, se salta ya que solo encierra ruido

        #cv2.imshow('Alimentos Clasificados', img)
        #cv2.waitKey()
        #cv2.destroyAllWindows()

        imgfile = "{}.jpg".format(datetime)

        kCal = {'aguacate':160, 'banano':122, 'huevo':105, 'manzana':52, 'salchicha':346}
        kcals = [kCal.get(i) for i in outputs] # Se mapean los resultados con sus calorias correspondientes

        # Se imprime en consola la siguiente informacion
        dt = datetime.partition("_") # Se obtiene una lista con la hora y la fecha sensada al momento de la captura
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
            print("Subiendo imagen {} al servicio de Google Cloud".format(imgfile))
            #https://cloud.google.com/storage/docs/quickstart-gsutil
            os.system("gsutil cp {} gs://mi_primer_deposito".format(imgfile))
            print("Hecho")
            os.remove(imgfile) # Se elimina la imagen almacenada localmente
        except:
            print("Error publicando la imagen")
        time.sleep(10)